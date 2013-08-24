<!DOCTYPE html>
<html lang="en">
<head>
<title><?= self::$_title ?></title>
<meta charset="utf-8" />
<? foreach (self::$_styles as $style): ?>
<link rel="stylesheet" type="text/css" href="<?= $style ?>?<?= self::$_mtime ?>" />
<? endforeach ?>
<? foreach (self::$_links as $link): ?>
<link rel="alternate" type="application/rss+xml" href="<?= $link ?>" />
<? endforeach ?>
<link rel="shortcut icon" type="image/ico" href="/global/media/favicon.ico" />
<? foreach (self::$_scripts as $script): ?>
<script type="text/javascript" src="<?= $script ?>"></script>
<? endforeach ?>
</head>
<body>
<div id="wrapper">
    <div id="header">
        <div class="wrap">
            <h1><a href="<? if (Nano_Session::user()): ?>/secure/<? else:?>/<? endif ?>">Flint</a></h1>
            <div id="navigation">
                <ul id="main-navigation"<? if (strpos($_SERVER['REQUEST_URI'], '/secure/repository/create') !== false): ?> class="alt"<? endif; ?>>
                    <? if (Nano_Session::user()): ?>
                    <li><a href="/secure/">Dashboard</a></li>
                    <li><a href="/secure/repository/create/">Create Repository</a></li>
                    <li><a href="/repositories/">Public Repositories</a></li>
                    <li><a href="/secure/account/">Account</a></li>
                    <li class="last"><a href="/secure/log-out/">Log Out</a></li>
                    <? else: ?>
                    <li><a href="/repositories/">Public Repositories</a></li>
                    <li><a href="/secure/create-account/">Create Account</a></li>
                    <li class="last"><a href="/secure/log-in/">Log In</a></li>
                    <? endif ?>
                </ul>
                <? if (Nano_Session::user()): ?>
                <ul id="sub-navigation">
                    <? if (strpos($_SERVER['REQUEST_URI'], '/secure/repository/create') !== false): ?>
                    <li><a href="/secure/repository/create/type/new/">New Repository</a></li>
                    <li><a href="/secure/repository/create/type/clone/">Clone Repository</a></li>
                    <li><a href="/secure/repository/create/type/upload/">Upload Repository</a></li>
                    <? endif; ?>
                    <li class="last"><a href="/user/<? echo Nano_Session::user()['username'] ?>/">My Public Repositories</a></li>
                </ul>
                <? endif ?>
            </div>
            <div class="clear"></div>
        </div>
    </div>
    <div class="wrap">
        <? if ($_SERVER['REQUEST_URI'] == '/'): ?>
        <div id="title">
            <h1><strong>Flint</strong> Fossil SCM Hosting</h1>
        </div>
        <? endif; ?>
        <div id="main">
            {{pagecontents}}
        </div>
        <div id="main-bottom"></div>
    </div>
    <div id="clear-footer"></div>
</div>
<div id="footer">
    <div class="wrap">
        <div class="left">
        </div>
        <div class="right">
        </div>
    </div>
</div>
</body>
</html>
