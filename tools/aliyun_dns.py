# -*- coding: utf-8 -*-
"""
阿里云云解析 DNS 通用调用脚本（RPC 风格，HMAC-SHA1 签名）。
凭证从本机 Workbench CLI 配置读取，不在代码中明文保存。
用法示例：
  python aliyun_dns.py list-domains
  python aliyun_dns.py list-records example.com
  python aliyun_dns.py add-record example.com dream A 203.0.113.10
  python aliyun_dns.py del-record <RecordId>
"""
import base64
import hashlib
import hmac
import json
import os
import sys
import urllib.parse
import urllib.request
import uuid
from datetime import datetime, timezone

ENDPOINT = "https://alidns.aliyuncs.com"
CONFIG_PATH = os.path.join(os.environ["USERPROFILE"], ".workbench", "config.json")


def load_ak():
    with open(CONFIG_PATH, "r", encoding="utf-8") as f:
        cfg = json.load(f)
    profile = cfg["profiles"][cfg["current"]]
    return profile["access_key_id"], profile["access_key_secret"]


def percent_encode(s):
    # 阿里云签名要求的 URL 编码规则：大写十六进制，安全字符为 -_.~
    return urllib.parse.quote(str(s), safe="-_.~")


def request(action, biz_params=None):
    ak_id, ak_secret = load_ak()
    params = {
        "Format": "JSON",
        "Version": "2015-01-09",
        "AccessKeyId": ak_id,
        "SignatureMethod": "HMAC-SHA1",
        "Timestamp": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
        "SignatureNonce": str(uuid.uuid4()),
        "SignatureVersion": "1.0",
        "Action": action,
    }
    if biz_params:
        params.update(biz_params)

    # 参数按字典序排序后构造规范化查询串
    items = sorted(params.items())
    query = "&".join(f"{percent_encode(k)}={percent_encode(v)}" for k, v in items)
    string_to_sign = "GET&" + percent_encode("/") + "&" + percent_encode(query)
    sign = hmac.new(
        (ak_secret + "&").encode("utf-8"),
        string_to_sign.encode("utf-8"),
        hashlib.sha1,
    ).digest()
    signature = base64.b64encode(sign).decode("utf-8")

    url = ENDPOINT + "/?" + query + "&Signature=" + percent_encode(signature)
    req = urllib.request.Request(url)
    with urllib.request.urlopen(req, timeout=20) as resp:
        return json.loads(resp.read().decode("utf-8"))


def main():
    if len(sys.argv) < 2:
        print("缺少子命令：list-domains / list-records / add-record / del-record")
        sys.exit(1)

    cmd = sys.argv[1]
    if cmd == "list-domains":
        data = request("DescribeDomains", {"PageSize": "100"})
        for d in data.get("Domains", {}).get("Domain", []):
            print(f"{d['DomainName']}\t解析状态:{d.get('RecordCount', '?')}条\t分组:{d.get('GroupId', '')}")
    elif cmd == "list-records":
        domain = sys.argv[2]
        data = request("DescribeDomainRecords", {"DomainName": domain, "PageSize": "100"})
        for r in data.get("DomainRecords", {}).get("Record", []):
            print(f"{r['RR']}.{r['DomainName']}\t{r['Type']}\t{r['Value']}\t记录ID:{r['RecordId']}")
    elif cmd == "add-record":
        domain, rr, rtype, value = sys.argv[2:6]
        data = request("AddDomainRecord", {
            "DomainName": domain, "RR": rr, "Type": rtype, "Value": value,
        })
        print("添加成功，记录ID：" + data.get("RecordId", ""))
    elif cmd == "del-record":
        data = request("DeleteDomainRecord", {"RecordId": sys.argv[2]})
        print("删除完成：" + json.dumps(data, ensure_ascii=False))
    else:
        print("未知子命令：" + cmd)


if __name__ == "__main__":
    main()
