<p>Help on Cloning</p>

<ol>
	<li>Anonymous Cloning:
		<ol>
			<li><tt>$ fossil clone https://<?= $_SERVER['SERVER_NAME'] ?>/user/<?= $this->user['username'] ?>/repository/<?= $this->repo['name'] ?> <?= $this->repo['name'] ?>.fossil</tt></li>
			<li><tt>$ fossil open <?= $this->repo['name'] ?>.fossil</tt></li>
		</ol>
	</li>
	<li>Authenticated Cloning:
		<ol>
			<li><tt>$ fossil clone https://<?= $this->user['username'] ?>@<?= $_SERVER['SERVER_NAME'] ?>/user/<?= $this->user['username'] ?>/repository/<?= $this->repo['name'] ?> <?= $this->repo['name'] ?>.fossil</tt></li>
			<li><tt>$ fossil open <?= $this->repo['name'] ?>.fossil</tt></li>
		</ol>
	</li>
</ol>
