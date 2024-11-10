<?php

declare(strict_types=1);

namespace PhpMyAdmin\Replication;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\UrlParams;

use function explode;
use function sprintf;

final class ReplicationInfo
{
    /** @var string[] */
    public array $primaryVariables = ['File', 'Position', 'Binlog_Do_DB', 'Binlog_Ignore_DB'];

    /** @var string[] */
    public array $replicaVariables = [
        'Slave_IO_State',
        'Replica_IO_State',
        'Master_Host',
        'Source_Host',
        'Master_User',
        'Source_User',
        'Master_Port',
        'Source_Port',
        'Connect_Retry',
        'Master_Log_File',
        'Source_Log_File',
        'Read_Master_Log_Pos',
        'Read_Source_Log_Pos',
        'Relay_Log_File',
        'Relay_Log_Pos',
        'Relay_Master_Log_File',
        'Relay_Source_Log_File',
        'Slave_IO_Running',
        'Replica_IO_Running',
        'Slave_SQL_Running',
        'Replica_SQL_Running',
        'Replicate_Do_DB',
        'Replicate_Ignore_DB',
        'Replicate_Do_Table',
        'Replicate_Ignore_Table',
        'Replicate_Wild_Do_Table',
        'Replicate_Wild_Ignore_Table',
        'Last_Errno',
        'Last_Error',
        'Skip_Counter',
        'Exec_Master_Log_Pos',
        'Exec_Source_Log_Pos',
        'Relay_Log_Space',
        'Until_Condition',
        'Until_Log_File',
        'Until_Log_Pos',
        'Master_SSL_Allowed',
        'Source_SSL_Allowed',
        'Master_SSL_CA_File',
        'Source_SSL_CA_File',
        'Master_SSL_CA_Path',
        'Source_SSL_CA_Path',
        'Master_SSL_Cert',
        'Source_SSL_Cert',
        'Master_SSL_Cipher',
        'Source_SSL_Cipher',
        'Master_SSL_Key',
        'Source_SSL_Key',
        'Seconds_Behind_Master',
        'Seconds_Behind_Source',
    ];

    /** @var mixed[] */
    private array $primaryStatus = [];

    /** @var mixed[] */
    private array $replicaStatus = [];

    /** @var mixed[] */
    private array $multiPrimaryStatus = [];

    /** @var mixed[] */
    private array $primaryInfo = [];

    /** @var mixed[] */
    private array $replicaInfo = [];

    public function __construct(private DatabaseInterface $dbi)
    {
    }

    public function load(string|null $connection = null): void
    {
        $this->setPrimaryStatus();

        if ($connection !== null && $connection !== '') {
            $this->setMultiPrimaryStatus();

            if ($this->multiPrimaryStatus !== []) {
                $this->setDefaultPrimaryConnection($connection);
                UrlParams::$params['primary_connection'] = $connection;
            }
        }

        $this->setReplicaStatus();
        $this->setPrimaryInfo();
        $this->setReplicaInfo();
    }

    private function setPrimaryStatus(): void
    {
        $this->primaryStatus = $this->dbi->fetchResult(Compatibility::getShowBinLogStatusStmt($this->dbi));
    }

    /** @return mixed[] */
    public function getPrimaryStatus(): array
    {
        return $this->primaryStatus;
    }

    private function setReplicaStatus(): void
    {
        if (
            $this->dbi->isMySql() && $this->dbi->getVersion() >= 80022
            || $this->dbi->isMariaDB() && $this->dbi->getVersion() >= 100501
        ) {
            $this->replicaStatus = $this->dbi->fetchResult('SHOW REPLICA STATUS');
        } else {
            $this->replicaStatus = $this->dbi->fetchResult('SHOW SLAVE STATUS');
        }
    }

    /** @return mixed[] */
    public function getReplicaStatus(): array
    {
        return $this->replicaStatus;
    }

    private function setMultiPrimaryStatus(): void
    {
        $this->multiPrimaryStatus = [];
        if ($this->dbi->isMariaDB() && $this->dbi->getVersion() >= 100501) {
            $this->multiPrimaryStatus = $this->dbi->fetchResult('SHOW ALL REPLICAS STATUS');
        } elseif ($this->dbi->isMariaDB()) {
            $this->multiPrimaryStatus = $this->dbi->fetchResult('SHOW ALL SLAVES STATUS');
        }
    }

    private function setDefaultPrimaryConnection(string $connection): void
    {
        $this->dbi->query(sprintf('SET @@default_master_connection = %s', $this->dbi->quoteString($connection)));
    }

    /**
     * @param mixed[] $status
     *
     * @return string[]
     */
    private function fill(array $status, string $key): array
    {
        if (empty($status[0][$key])) {
            return [];
        }

        return explode(',', $status[0][$key]);
    }

    private function setPrimaryInfo(): void
    {
        $this->primaryInfo = ['status' => $this->primaryStatus !== []];

        if (! $this->primaryInfo['status']) {
            return;
        }

        $this->primaryInfo['Do_DB'] = $this->fill($this->primaryStatus, 'Binlog_Do_DB');
        $this->primaryInfo['Ignore_DB'] = $this->fill($this->primaryStatus, 'Binlog_Ignore_DB');
    }

    /** @return mixed[] */
    public function getPrimaryInfo(): array
    {
        return $this->primaryInfo;
    }

    private function setReplicaInfo(): void
    {
        $this->replicaInfo = ['status' => $this->replicaStatus !== []];

        if (! $this->replicaInfo['status']) {
            return;
        }

        $this->replicaInfo['Do_DB'] = $this->fill($this->replicaStatus, 'Replicate_Do_DB');
        $this->replicaInfo['Ignore_DB'] = $this->fill($this->replicaStatus, 'Replicate_Ignore_DB');
        $this->replicaInfo['Do_Table'] = $this->fill($this->replicaStatus, 'Replicate_Do_Table');
        $this->replicaInfo['Ignore_Table'] = $this->fill($this->replicaStatus, 'Replicate_Ignore_Table');
        $this->replicaInfo['Wild_Do_Table'] = $this->fill($this->replicaStatus, 'Replicate_Wild_Do_Table');
        $this->replicaInfo['Wild_Ignore_Table'] = $this->fill($this->replicaStatus, 'Replicate_Wild_Ignore_Table');
    }

    /** @return mixed[] */
    public function getReplicaInfo(): array
    {
        return $this->replicaInfo;
    }
}
