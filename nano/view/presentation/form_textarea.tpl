<label for="<?= $this->id ?>"><?= $this->label ?>: </label>
<textarea tabindex="<?= $this->idx ?>" id="<?= $this->id ?>" name="<?= $this->id ?>" rows="10" cols="40"><? if (isset($_POST[$this->id])): ?><?= htmlentities($_POST[$this->id]) ?><? elseif($this->default): ?><?= $this->default ?><? endif ?></textarea>
<? if (isset($this->errors)): ?>
<strong>
    <? foreach ($this->errors as $error): ?>
    <?= $error ?> 
    <? endforeach ?>
    </strong>
<? endif ?>
