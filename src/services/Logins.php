<?php
namespace verbb\knockknock\services;

use verbb\knockknock\KnockKnock;
use verbb\knockknock\helpers\IpHelper;
use verbb\knockknock\models\Login;
use verbb\knockknock\models\Settings;
use verbb\knockknock\records\Login as LoginRecord;

use Craft;
use craft\db\Query;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;

use yii\base\Component;
use yii\base\Exception;

class Logins extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * @return Login[]
     */
    public function getAllLogins(): array
    {
        $logins = [];
        $results = $this->_createLoginQuery()->all();

        foreach ($results as $result) {
            $logins[] = new Login($result);
        }

        return $logins;
    }

    public function getLoginsByIp($ipAddress): ?Login
    {
        $result = $this->_createLoginQuery()
            ->where(['ipAddress' => $ipAddress])
            ->one();

        return $result ? new Login($result) : null;
    }

    public function saveLogin(Login $login, bool $runValidation = true): bool
    {
        if ($runValidation && !$login->validate()) {
            Craft::info('Login not saved due to validation error.', __METHOD__);
            return false;
        }

        $loginRecord = $this->_getLoginRecordById($login->id);
        $loginRecord->ipAddress = $login->ipAddress;
        $loginRecord->password = $login->password;

        $loginRecord->save(false);

        if (!$login->id) {
            $login->id = $loginRecord->id;
        }

        return true;
    }

    public function checkLockout($ipAddress): bool
    {
        /* @var Settings $settings */
        $settings = KnockKnock::$plugin->getSettings();

        // Check for allow/deny
        if (IpHelper::ipInCidrList($ipAddress, $settings->getAllowIps())) {
            return false;
        }

        if (IpHelper::ipInCidrList($ipAddress, $settings->getDenyIps())) {
            return true;
        }

        $interval = DateTimeHelper::secondsToInterval($settings->invalidLoginWindowDuration);
        $start = DateTimeHelper::currentUTCDateTime()->sub($interval);
        $end = DateTimeHelper::currentUTCDateTime();

        // Find the total attempts for this IP and the give date range
        $loginAttempts = $this->_createLoginQuery()
            ->where(['ipAddress' => $ipAddress])
            ->andWhere(['between', 'dateCreated', Db::prepareDateForDb($start), Db::prepareDateForDb($end)])
            ->count();
        return $loginAttempts >= $settings->maxInvalidLogins;
    }


    // Private methods
    // =========================================================================

    private function _createLoginQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'ipAddress',
                'password',
                'dateCreated',
                'dateUpdated',
            ])
            ->from(['{{%knockknock_logins}}']);
    }

    private function _getLoginRecordById(int $loginId = null): ?LoginRecord
    {
        if ($loginId !== null) {
            $loginRecord = LoginRecord::findOne(['id' => $loginId]);

            if ($loginRecord === null) {
                throw new Exception(Craft::t('knock-knock', 'No login exists with the ID “{id}”.', ['id' => $loginId]));
            }
        } else {
            $loginRecord = new LoginRecord();
        }

        return $loginRecord;
    }
}
