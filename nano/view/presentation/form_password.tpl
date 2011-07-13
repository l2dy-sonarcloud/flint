<label for="<?= $this->id ?>"><?= $this->label ?>: </label>
<input type="password" tabindex="<?= $this->idx ?>" id="<?= $this->id ?>" name="<?= $this->id ?>" />
<? if (isset($this->errors)): ?>
<strong>
    <? foreach ($this->errors as $error): ?>
    <?= $error ?> 
    <? endforeach ?>
    </strong>
<? endif ?>
