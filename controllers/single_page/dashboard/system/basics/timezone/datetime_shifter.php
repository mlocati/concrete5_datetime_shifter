<?php
namespace Concrete\Package\DatetimeShifter\Controller\SinglePage\Dashboard\System\Basics\Timezone;

use Concrete\Core\Page\Controller\DashboardPageController;
use Symfony\Component\HttpFoundation\JsonResponse;

class DatetimeShifter extends DashboardPageController
{
    public function view()
    {
        $this->set('tableFields', $this->getTablesAndFields());
    }

    protected function getTablesAndFields()
    {
        $db = $this->app->make('Concrete\Core\Database\Connection\Connection');
        /* @var \Concrete\Core\Database\Connection\Connection $db */
        $sm = $db->getSchemaManager();
        $platform = $sm->getDatabasePlatform();
        $result = array();
        $tableNames = $sm->listTableNames();
        natcasesort($tableNames);
        foreach ($tableNames as $tableName) {
            $datetimeFields = array();
            $sql = $platform->getListTableColumnsSQL($tableName);
            $rs = $db->executeQuery($sql);
            while ($row = $rs->fetch()) {
                $row = array_change_key_case($row, CASE_LOWER);
                if (preg_match('/^\s*datetime\s*($|\()/i', $row['type'])) {
                    $datetimeFields[] = $row['field'];
                }
            }
            $rs->closeCursor();
            if (!empty($datetimeFields)) {
                natcasesort($datetimeFields);
                $result[$tableName] = $datetimeFields;
            }
        }

        return $result;
    }

    public function updateDatetimeField()
    {
        $errors = $this->app->make('error');
        /* @var \Concrete\Core\Error\Error $errors */
        if (!$this->token->validate('dts-update-field')) {
            $errors->add($this->token->getErrorMessage());
        } else {
            $valn = $this->app->make('helper/validation/numbers');
            $tableFields = $this->getTablesAndFields();
            $tablename = $this->post('tablename');
            if (!is_string($tablename) || $tablename === '' || !isset($tableFields[$tablename])) {
                $errors->add('Invalid field: tablename');
            } else {
                $fields = $tableFields[$tablename];
                $fieldname = $this->post('fieldname');
                if (!is_string($fieldname) || $fieldname === '' || !in_array($fieldname, $fields, true)) {
                    $errors->add('Invalid field: fieldname');
                }
            }
            switch ($this->post('operation')) {
                case '+':
                    $sqlFunction = 'DATE_ADD';
                    break;
                case '-':
                    $sqlFunction = 'DATE_SUB';
                    break;
                default:
                    $errors->add('Invalid field: operation');
                    break;
            }
            $hours = $this->post('hours');
            if (!$valn->integer($hours)) {
                $errors->add('Invalid field: hours');
            } else {
                $hours = (int) $hours;
                if ($hours < 0) {
                    $errors->add('Invalid field: hours');
                }
            }
            $minutes = $this->post('minutes');
            if (!$valn->integer($minutes)) {
                $errors->add('Invalid field: minutes');
            } else {
                $minutes = (int) $minutes;
                if ($minutes < 0 || $minutes > 59) {
                    $errors->add('Invalid field: minutes');
                }
            }
            if ($hours === 0 && $minutes === 0) {
                $errors->add('Invalid fields: hours/minutes');
            }
            $dh = $this->app->make('date');
            /* @var \Concrete\Core\Localization\Service\Date $dh */
            $limitMin = $this->post('limitMin');
            if ($limitMin !== '') {
                if (!is_string($limitMin) || !preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}(:\d{2})?)$/', $limitMin, $matches)) {
                    $errors->add('Invalid field: limitMin');
                } else {
                    $limitMin = $dh->toDateTime("{$matches[1]} {$matches[2]}", 'user', 'system')->format($dh::DB_FORMAT);
                }
            }
            $limitMax = $this->post('limitMax');
            if ($limitMax !== '') {
                if (!is_string($limitMax) || !preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}(:\d{2})?)$/', $limitMax, $matches)) {
                    $errors->add('Invalid field: limitMax');
                } else {
                    $limitMax = $dh->toDateTime("{$matches[1]} {$matches[2]}", 'user', 'system')->format($dh::DB_FORMAT);
                }
            }
            if (!$errors->has()) {
                $db = $this->app->make('Concrete\Core\Database\Connection\Connection');
                /* @var \Concrete\Core\Database\Connection\Connection $db */
                $platform = $db->getDatabasePlatform();
                $quotedTablename = $platform->quoteSingleIdentifier($tablename);
                $quotedFieldname = $platform->quoteSingleIdentifier($fieldname);
                $sql = <<<EOT
UPDATE
    $quotedTablename
SET
    $quotedFieldname = $sqlFunction($quotedFieldname, INTERVAL '$hours:$minutes' HOUR_MINUTE)
WHERE
    ($quotedFieldname IS NOT NULL)
    AND ($quotedFieldname != '0000-00-00 00:00:00') 
EOT
                ;
                if ($limitMin !== '') {
                    $sql .= " AND ($quotedFieldname >= '$limitMin')";
                }
                if ($limitMax !== '') {
                    $sql .= " AND ($quotedFieldname <= '$limitMax')";
                }
                $numUpdated = (int) $db->executeQuery($sql)->rowCount();
            }
        }
        if ($errors->has()) {
            $response = JsonResponse::create(array('error' => true, 'errors' => $errors->getList()));
        } else {
            $response = JsonResponse::create(array('result' => t2('%d record has been updated', '%d records have been updated', $numUpdated)));
        }

        return $response;
        dd($errors);
    }
}
