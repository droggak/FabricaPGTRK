<?php use App\Core\Input; ?>
<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Вход — Фабрика новостей</title><link rel="stylesheet" href="/css/app.css"></head><body>
<div id="auth-screen"><div class="auth-box">
<div class="auth-logo">Редакционная система</div>
<div class="auth-title">Фабрика<br>новостей</div>
<?php if(!empty($error)): ?><div class="flash flash-error" style="margin-bottom:16px"><?=Input::e($error)?></div><?php endif; ?>
<form method="POST" action="/login" autocomplete="off">
<?=Input::csrfField()?>
<div class="form-group"><label>Логин</label><input type="text" name="login" required autofocus value="<?=Input::e($_POST['login']??'')?>"></div>
<div class="form-group"><label>Пароль</label><input type="password" name="password" required></div>
<button type="submit" class="btn btn-primary">Войти</button>
</form>
</div></div></body></html>
