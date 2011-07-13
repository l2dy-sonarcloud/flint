<label for="<?= $this->id ?>"><?= $this->label ?>: </label>
<input type="text" tabindex="<?= $this->idx ?>" id="<?= $this->id ?>" name="<?= $this->id ?>"<? if (isset($_POST[$this->id])): ?> value="<?= htmlentities($_POST[$this->id]) ?>"<? elseif ($this->default): ?> value="<?= $this->default ?>"<? endif ?> />
<? if (isset($this->errors)): ?>
<strong>
    <? foreach ($this->errors as $error): ?>
    <?= $error ?> 
    <? endforeach ?>
    </strong>
<? endif ?>
