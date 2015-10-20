<?php

/**
 * Private Assests
 * Requires login to view/download file from private folders
 *
 * Rules must be defined in the htaccess and in the config.yml
 * A sample htaccess file is provided in /ressources
 *
 * File audit is also available to track file access. If you want to track all
 * file access, simple route all assets to this controller with a public rule.
 *
 * Heavily inspired by ss-privateassets module but add the functionnality to allow access to owner only
 *
 * @author LeKoala
 * @original_author  Thierry Francois @colymba thierry@colymba.com
 * @link https://github.com/colymba/ss-privateassets
 */
class DevToolkitAssetsController extends Controller
{
    const RULE_LOGGED_IN = 'logged-in';
    const RULE_OWNER     = 'owner';
    const RULE_PUBLIC    = 'public';
    const RULE_ADMIN     = 'admin';
    const RULE_NOT_FOUND = 'not-found'; // reserved rule

    public static $allowed_actions = array('index');

    /**
     * Output file to user.
     * Send file content to browser for download progressively.
     */
    public function index()
    {
        $file          = $this->request->getVar('file');
        $fileAssetPath = substr($file, stripos($file, 'assets'));
        $fileObj       = File::get()->filter(array('Filename' => Convert::raw2sql($fileAssetPath)))->first();

        if ($fileObj) {

            $rule = self::RULE_PUBLIC;
            foreach (self::config()->rules as $key => $value) {
                $regex = '$^assets/'.trim($key, '/').'/$';
                if (preg_match($regex, $fileAssetPath)) {
                    $rule = $value;
                }
            }

            if (self::config()->cms_access_ignore_rules && Permission::check('CMS_ACCESS')) {
                $rule = self::RULE_PUBLIC;
            }
            if (self::config()->admin_ignore_rules && Permission::check('ADMIN')) {
                $rule = self::RULE_PUBLIC;
            }

            switch ($rule) {
                case self::RULE_PUBLIC:
                    // Then we do nothing...
                    break;
                case self::RULE_ADMIN:
                    if (!Permission::check('ADMIN')) {
                        return $this->sendHttpError($fileObj, $rule);
                    }
                    break;
                case self::RULE_LOGGED_IN:
                    if (!Member::currentUserID()) {
                        return $this->sendHttpError($fileObj, $rule);
                    }
                    break;
                case self::RULE_OWNER:
                    if ($fileObj->OwnerID != Member::currentUserID()) {
                        return $this->sendHttpError($fileObj, $rule);
                    }
                    break;
                default:
                    throw new Exception("Rule $rule is not defined");
            }

            $filePath = $fileObj->getFullPath();
            $mimeType = HTTP::get_mime_type($filePath);
            $name     = $fileObj->Name;

            $this->audit($fileObj);

            header("Content-Type: $mimeType");
            header("Content-Disposition: attachment; filename=\"$name\"");
            header("Pragma: public");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            set_time_limit(0);
            $file = @fopen($filePath, "rb");
            while (!feof($file)) {
                print(@fread($file, 1024 * 8));
                ob_flush();
                flush();
            }
            exit;
        }

        return $this->sendHttpError($fileObj, self::RULE_NOT_FOUND);
    }

    protected function audit($file, $rule = null)
    {
        if (!$this->config()->enable_audit) {
            return;
        }
        $audit = new FileAudit();
        if ($rule) {
            $audit->Error      = true;
            $audit->FailedRule = $rule;
        }
        if ($file) {
            $audit->FileID = $file->ID;
        }
        if ($id = Member::currentUserID()) {
            $audit->MemberID = $id;
        }
        $audit->write();
    }

    protected function sendHttpError($file, $rule)
    {
        $this->audit($file, $rule);

        switch ($rule) {
            case self::RULE_ADMIN:
                $message = _t('DevToolkitAssetsController.MUSTBEADMIN',
                    'You must be an admin to see this file');
                break;
            case self::RULE_PUBLIC:
                break;
            case self::RULE_LOGGED_IN:
                $message = _t('DevToolkitAssetsController.NOTLOGGEDIN',
                    'You must be logged in to see this file');
                break;
            case self::RULE_NOT_FOUND:
                $message = _t('DevToolkitAssetsController.FILENOTFOUND',
                    "The requested file was not found");
                break;
            case self::RULE_OWNER:
                $message = _t('DevToolkitAssetsController.NOTTHEOWNER',
                    'You are not the owner of this file');
                break;
            default:
                $message = _t('DevToolkitAssetsController.DEFAULTERROR',
                    "The requested file was not found");
                break;
        }

        return $this->httpError(404, $message);
    }
}

class FileAudit extends DataObject
{
    private static $db      = array(
        'Error' => 'Boolean',
        'FailedRule' => 'Varchar',
        'Ip' => 'Varchar(255)',
    );
    private static $has_one = array(
        'File' => 'File',
        'Member' => 'Member'
    );

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->Ip) {
            $this->Ip = self::getRealIp();
        }
    }

    /**
     * Get the ip of the client
     *
     * @return string
     */
    public static function getRealIp()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            $ip = array_pop($ip);
        } else if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        } else if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (isset($ip) && filter_var($ip, FILTER_VALIDATE_IP) !== false) {
            return $ip;
        }

        return '0.0.0.0';
    }
}