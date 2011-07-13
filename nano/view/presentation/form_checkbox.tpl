<label for="<?= $this->id ?>"><?= $this->label ?> </label>
<input type="checkbox" tabindex="<?= $this->idx ?>" id="<?= $this->id ?>" name="<?= $this->id ?>"<? if (isset($_POST[$this->id])): ?> checked="checked"<? elseif ($this->default): ?> checked="checked"<? endif ?> />
<? if (isset($this->errors)): ?>
<strong>
    <? foreach ($this->errors as $error): ?>
    <?= $error ?> 
    <? endforeach ?>
    </strong>
<? endif ?>
