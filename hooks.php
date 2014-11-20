<?php
/**
 * this is the Place for global available Hooks
 *
 * it is included on every Backend-Action.
 * put global available Hook-Functions to this File
 */


/**
 * synchronize (some of the Fields of) an Entry to other Projects
 * if the valid Project-Name is -comma-separated- defined in a field ($params[0])
 *
 * Schema
 * PST:sync:SYNCFIELD[,FIELD_1|FIELD_2|FIELD_3|...]
 * Example
 * PST:sync:sync,username|password
 * @param mixed
 *
 */
function sync($params)
{
    global $action, $output, $projectName, $objectName;

    if ($action != 'saveContent') return;

    // check for the ID
    if (is_numeric($output)) {
        // get the original Entry
        $n = $projectName . '\\' . $objectName;
        $o = new $n();
        $e = $o->Get($output);

        // check for the sync_to - Field
        if (!empty($e->{$params[0]})) {
            // get the fields to update across projects
            $fields = (!empty($params[1])) ? explode('|', $params[1]) : array_keys(get_object_vars($e));

            $ps = explode(',', $e->{$params[0]});

            // loop the Projects
            foreach ($ps as $p) {
                // if another project is detected ...
                if ($p != $projectName) {
                    // ... and existing
                    $c = dirname(dirname(dirname(__DIR__))) . '/projects/' . $p . '/objects/class.' . $objectName . '.php';
                    if (file_exists($c)) {
                        // ... get the entry
                        include_once $c;
                        $nn = $p . '\\' . $objectName;
                        $on = new $nn();
                        $en = $on->Get($output);

                        // ensure that the id is catched (to create the entry with the same id)
                        $en->id = $output;

                        // update the entry field by field
                        foreach ($fields as $field) {
                            $en->{$field} = $e->{$field};
                        }

                        if (!$en->Save()) $output .= ' [[' . $p . ' was not updated!]] ';
                    } else {
                        $output .= ' [[' . $p . ' does not exist!]] ';
                    }
                }
            }
        }
    }
}

/**
 * prevents the creation of doublettes
 * PRE:nodouble:FIELDNAME
 */
function nodouble($params)
{
    global $projectName, $objectName, $objectId;

    if (empty($_POST[$params[0]])) return;
    $n = $projectName . '\\' . $objectName;
    $o = new $n();
    $e = $o->GetList(array(
            array($params[0], '=', $_POST[$params[0]]),
            array('id', '!=', $objectId)
        )
    );
    if (count($e) > 0) {
        exit('[[' . $params[0] . ' must be unique!]]');
    }
}

/**
 * delete all Files within a specified Folder (relatively to the Project-Folder)
 * PRE:clearcache:FOLDERNAME
 */
function clearcache($params)
{
    global $projectPath;
    $path = $projectPath . '/' . $params[0];
    if ($_GET['action'] === 'saveContent' && file_exists($path)) {
        foreach (glob($path . '/*') as $c) {
            unlink($c);
        }
    }
}

/**
 * measure + show Script-Execution-Time and used Memory
 *
 * to use this you have to call this Hook twice (at the Beginning and the End) !!
 *
 * PRE:measurePerformance:start
 * and
 * PST:measurePerformance:stop
 *
 * @params mixed array containing a String wheter to start or to stop/output the Measurement
 */
function measurePerformance($params)
{
    switch ($params[0]) {
        case 'start':
            define('SCRIPT_START', microtime(true));
            break;
        case 'stop':
            if (defined('SCRIPT_START')) {
                $asec = microtime(true) - SCRIPT_START;
                echo '<small>generated in: ' . number_format($asec, 5, ',', '') . ' SEC, used Memory: ' . number_format(memory_get_peak_usage() / 1024, 0, '', "'") . ' KB</small>';
            }
            break;
    }
}

/**
 * for Development-Purposes only!!
 * this Hook shows temporarily all the hidden Replacement inside the Output-Stream
 *
 */
function showPSTreplacements()
{
    global $output;
    $output = str_replace(array('<!--', '-->'), array('<blink>&lt;!--', '--&gt;</blink>'), $output);
}

/**
 * Function "Content-Copy"
 *
 * Allows Easy Setup of Workspaces, Preview-staging etc.
 * For Workspaces with User-restriction just limit access to the Fields {$if} and {$to}
 * If Field {$params[0]} is checked, copy Content from {$params[1]} to {$params[2]} and reset {$params[0]}
 *
 * Field {$params[0]} should be Boolean (Checkbox)
 * Fields {$params[1]} and {$params[2]} should have the same Type
 *
 * Example-Call: PRE:ccopy:goonline,workspace,weboutput
 * @param mixed $params [if, from, to]
 */
function ccopy($params)
{
    if ($_GET['action'] === 'saveContent' && count($params) == 3) {
        if ($_POST[$params[0]] == 1) {
            $_POST[$params[2]] = $_POST[$params[1]];
            $_POST[$params[0]] = 0;
        }
    }
}


/**
 * convert Markdown to XHTML
 * additionally create Thumbnails from embedded Images ( saved to "files/.tmb2/" )
 * Usage:
 * PRE:mark2html:fromField,toField[,thumbwidth,tumbheight]
 * @param mixed
 */
function mark2html($params)
{
    global $c, $projectPath;

    if (
        ($_GET['action'] === 'saveContent' || $_GET['action'] === 'updateContent') // listen to action
        && strlen(trim($_POST[$params[0]])) > 0 // only overwrite "toField" if fromField is not empty
    ) {
        $confs = array('ppath' => $projectPath . '/',
            'thumbWidth' => ($params[2] ? intval($params[2]) : 100),
            'thumbHeight' => ($params[3] ? intval($params[3]) : 100)
        );

        // this file is included by backend/crud.php so we simply need to point to inc/...
        require_once '../vendor/michelf/php-markdown/Michelf/MarkdownExtra.inc.php';

        // transform Markdown to Html
        $html = Michelf\MarkdownExtra::defaultTransform($_POST[$params[0]]);

        //$html = [start=

        // if target-field starts with e_ we base64_encode html
        if (substr($params[1], 0, 2) == 'e_') {
            $html = base64_encode($html);
        }

        $c->inject[$params[1]] = $html;
    }
}

/**
 *
 * PRE:loadGenericModel:model_field
 *
 * @param mixed
 */
function loadGenericModel($params)
{
    global $objectId, $c, $projectPath, $action;

    if ($action === 'saveContent') {


        // if template_name exists and contains a string we assume that we have to load a model
        if (!empty($_POST[$params[0]]['__TEMPLATE_SELECT__']['value']) && $_POST[$params[0]]['__TEMPLATE_SELECT__']['value'] != '_empty_') {
            $templateName = preg_replace('/\W/', '', $_POST[$params[0]]['__TEMPLATE_SELECT__']['value']);

            // generic model
            $genericModelPath = $projectPath . '/objects/generic/' . $templateName . '.php';

            if (file_exists($genericModelPath)) {
                /** @var $genericObject array will be overwritten by the dynamically included model */
                $genericObject = array();
                include_once $genericModelPath;

                // now we have to prepare the fields           '__TEMPLATE__' => array('value' => $templateName)
                $fields = array();


                foreach ($genericModel[$templateName] as $k => $v) {
                    $fields[$k] = array('value' => $v['default']);
                }

                // fill the MODEL with the array
                $_POST[$params[0]] = $fields;

            } else {
                unset($_POST[$params[0]]);
                exit;
            }
        }
    }
}


/**
 * fills Content from a given File into a Field and "reset" the Path.
 *
 * Optional fill the File-Name as Flag into anoter Field
 * Usage:
 * PRE:prefill:path_field,into_field[,flag_field]
 * @param mixed
 */
function prefill($params)
{
    global $projectPath;
    $path = $projectPath . '/' . $_POST[$params[0]];
    if ($_GET['action'] === 'saveContent' && strlen(trim($_POST[$params[0]])) > 0 && $content = file_get_contents($path)) {
        $_POST[$params[0]] = ''; //reset the Path-Field
        $_POST[$params[1]] = $content; // load the Content into the desired Field
        if (isset($params[2])) $_POST[$params[2]] = preg_replace(array('/^[0-9]+/', '/_/'), array('', ' '), pathinfo($path, PATHINFO_FILENAME)); // fill the File-Name into the Flag-Field if defined
    }
}

/**
 *
 *
 */
function teamwork()
{
    global $action, $projectName, $objectName, $objectId, $output;
    if ($objectId == 0) return;

    $user = $_SESSION[$projectName]['special']['user'];

    require dirname(__DIR__) . '/teamwork/sharedMemory.php';
    //
    $sid = substr(preg_replace('/[^1-9]/Uis', '', sha1($_SESSION[$projectName]['secret'] . $projectName . $objectName . $objectId)), 0, 18);
    $mem = new SharedMemory(111);
    $item = $mem->Get();

    //if(!is_array($item)) $item = array('msg'=>array(),'user'=>array());

    switch ($action) {
        case 'none':
            /*if (!empty($_POST['leaveContent']))
            {
                $osid = substr(preg_replace('/[^1-9]/Uis', '', sha1($_SESSION[$projectName]['secret'].$projectName.$_POST['leaveContent']['obj'].$_POST['leaveContent']['id'])), 0, 18);
                $oldmem = new SharedMemory($osid);
                $olditem = $oldmem->Get();
                unset($olditem['user'][$user['id']]);
                $oldmem->Set($olditem);

            }*/

            break;
        case 'getContent':
            $output = str_replace(
                    array('id="saveButton"', 'id="deleteButton"'),
                    array('id="saveButton" disabled="disabled"', 'id="deleteButton" disabled="disabled"'),
                    $output
                )
                . '<script src="extensions/teamwork/watch.js.php?project=' . $projectName . '&object=' . $objectName . '&objectId=' . $objectId . '"></script>';
            break;
        case 'saveContent':

            break;
        case 'deleteContent':

            break;
        case 'sdfsdf':

            break;
    }
    /*
    if ($action === 'none' && !empty($_POST['leaveContent']))
    {


        //$sharedMem->{$projectName}->{$_POST['leaveContent']['obj']}->{$_POST['leaveContent']['id']}
    }
    if ($action === 'getContent')
    {
        //global $output, $projectName, $objectName, $objectId;
        //$s = http_build_query($_SESSION[$projectName]['special']['user']);
        //$output = '<iframe style="width:65px;height:25px;float:right" border="0" frameborder="0" src="extensions/teamwork/?pn='.$projectName.'&on='.$objectName.'&oid='.$objectId.'&'.$s.'"></iframe>'.$output;


    }*/
}

?>
