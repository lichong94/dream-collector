<?php
require_once __DIR__ . '/init.php';
require_login();

$me = current_user_id();
$id = (int)($_GET['id'] ?? 0);
$record = null;
if ($id > 0) {
    $record = db_one("SELECT * FROM dream_log WHERE id = ?", [$id]);
    if (!$record) redirect('/');
    // 仅作者本人可编辑自己的记录
    if ((int)$record['user_id'] !== $me) {
        http_response_code(403);
        exit('无权编辑这条梦境记录。');
    }
}

$isEdit = $record !== null;
$page_meta = [
    'title' => ($isEdit ? '编辑梦境' : '记录新梦境') . ' - ' . SITE_NAME,
    'description' => '在梦境收集器中填写梦境标题、做梦日期、心情标签与详细内容，保存后可随时回顾与编辑。',
    'canonical' => SITE_URL . '/edit.php' . ($isEdit ? ('?id=' . $id) : ''),
    'type' => 'website',
    'robots' => 'noindex',
];
$active_nav = 'edit';
require_once __DIR__ . '/header.php';

// 回填值（含表单校验失败后的回显，此处由 save.php 跳转带回）
$old = $_SESSION['form_old'] ?? null;
unset($_SESSION['form_old']);
$title = $old['title'] ?? ($record['title'] ?? '');
$dreamDate = $old['dreamDate'] ?? ($record['dream_date'] ?? date('Y-m-d'));
$mood = $old['mood'] ?? ($record['mood'] ?? '平淡');
$tags = $old['tags'] ?? ($record['tags'] ?? '');
$content = $old['content'] ?? ($record['content'] ?? '');
$visibility = $old['visibility'] ?? ($record['visibility'] ?? 'public');
?>

<div class="layout">
  <section style="grid-column: 1 / -1;">
    <div class="card" style="max-width: 720px;">
      <h1 class="card-title"><?php echo $isEdit ? '编辑梦境' : '记录新梦境'; ?></h1>

      <?php if (!empty($_SESSION['form_error'])): ?>
        <div class="alert alert-error"><?php echo e($_SESSION['form_error']); ?></div>
        <?php unset($_SESSION['form_error']); ?>
      <?php endif; ?>

      <form method="post" action="/save.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <?php if ($isEdit): ?>
          <input type="hidden" name="id" value="<?php echo (int)$record['id']; ?>">
        <?php endif; ?>

        <div class="form-item">
          <label for="title">梦境标题 <span style="color:var(--danger);">*</span></label>
          <input type="text" id="title" name="title" required maxlength="120"
                 placeholder="用一句话概括这个梦" value="<?php echo e($title); ?>">
        </div>

        <div class="form-item">
          <label for="dream_date">做梦日期 <span style="color:var(--danger);">*</span></label>
          <input type="date" id="dream_date" name="dream_date" required value="<?php echo e($dreamDate); ?>">
        </div>

        <div class="form-item">
          <label for="mood">醒来心情</label>
          <select id="mood" name="mood">
            <?php foreach (mood_options() as $m): ?>
              <option value="<?php echo e($m); ?>" <?php echo $mood === $m ? 'selected' : ''; ?>>
                <?php echo e($m); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-item">
          <label for="tags">标签</label>
          <input type="text" id="tags" name="tags" maxlength="255"
                 placeholder="多个标签用逗号分隔，例如：飞行,童年,考试" value="<?php echo e($tags); ?>">
          <div class="hint">便于按主题检索梦境，可留空。</div>
        </div>

        <div class="form-item">
          <label for="content">梦境详细内容 <span style="color:var(--danger);">*</span></label>
          <textarea id="content" name="content" required
                    placeholder="写下你记得的情节、人物、场景和感受……"><?php echo e($content); ?></textarea>
        </div>

        <div class="form-item">
          <label>可见性</label>
          <label class="radio-line">
            <input type="radio" name="visibility" value="public" <?php echo $visibility === 'private' ? '' : 'checked'; ?>>
            <span>公开：在网站首页展示给访客浏览</span>
          </label>
          <label class="radio-line">
            <input type="radio" name="visibility" value="private" <?php echo $visibility === 'private' ? 'checked' : ''; ?>>
            <span>隐私：只有你本人可以看到，他人无法查看</span>
          </label>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <button class="btn btn-primary" type="submit">保存梦境</button>
          <a class="btn btn-ghost" href="/">取消</a>
        </div>
      </form>
    </div>
  </section>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
