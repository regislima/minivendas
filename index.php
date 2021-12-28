<?php

use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Dialog\TMessage;

require_once 'init.php';

$theme = $ini['general']['theme'];
$class  = isset($_REQUEST['class']) ? $_REQUEST['class'] : '';

new TSession;

# Verifica se está logado
if (TSession::getValue('logged')) {    
    $menu_string = '';

    try {
        TTransaction::open($ini['database']['database_file_name']);
        $user = Usuario::find(TSession::getValue('userid'));
        TTransaction::close();
    } catch (Exception $e) {
        new TMessage('error', $e->getMessage());
    }

    # Verifica de o usuário logado é administrador
    if ($user->nivel == 1) {
        $menu_string = AdiantiMenuBuilder::parse('menu.xml', $theme);
    }

    # Verifica de o usuário logado é vendedor
    if ($user->nivel == 2) {
        $menu_string = AdiantiMenuBuilder::parse('menu-vendedor.xml', $theme);
    }

    $username = "{$user->nome} {$user->sobrenome}";
    
    $content = file_get_contents("app/templates/{$theme}/layout.html");
    $content = str_replace('{MENU}', $menu_string, $content);
    $content = str_replace('{username}', $username, $content);
    $content = str_replace('{id}', TSession::getValue('userid'), $content);
} else {
    $content = file_get_contents("app/templates/{$theme}/login.html");
}

$css = TPage::getLoadedCSS();
$js = TPage::getLoadedJS();
$content = str_replace('{LIBRARIES}', file_get_contents("app/templates/{$theme}/libraries.html"), $content);
$content = str_replace('{HEAD}', $css . $js, $content);
$content = str_replace('{template}', $theme, $content);

echo $content;

if (TSession::getValue('logged')) {
    if ($class) {
        $method = isset($_REQUEST['method']) ? $_REQUEST['method'] : null;
        AdiantiCoreApplication::loadPage($class, $method, $_REQUEST);
    } else {
        AdiantiCoreApplication::loadPage('Dash');
    }
} else {
    AdiantiCoreApplication::loadPage('LoginForm', null, $_REQUEST);
}
