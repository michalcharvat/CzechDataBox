<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Service;

use MichalCharvat\CzechDataBox\Dto\CreditInfo;
use MichalCharvat\CzechDataBox\Dto\DataBoxState;
use MichalCharvat\CzechDataBox\Dto\OwnerInfo;
use MichalCharvat\CzechDataBox\Input\OwnerSearch;
use MichalCharvat\CzechDataBox\Internal\Normalize as N;

/**
 * db_search.wsdl — endpoint …/DS/df. Informational statuses 0002 (nothing found), 0003 (limit reached)
 * and 0009 (box does not accept PDZ) are successes: the result is simply empty or truncated.
 */
final class Search extends AbstractService
{
    /**
     * @deprecated Use findDataBox2().
     * @param array<string, mixed> $dbOwnerInfo tDbOwnerInfo fields (pnFirstName, pnMiddleName, …)
     */
    public function findDataBox(array $dbOwnerInfo): \stdClass
    {
        return $this->call('FindDataBox', ['dbOwnerInfo' => $dbOwnerInfo]);
    }

    /** @return list<OwnerInfo> */
    public function findDataBox2(OwnerSearch $q): array
    {
        $raw = $this->call('FindDataBox2', ['dbOwnerInfo' => $q->toSoap()]);
        return array_map(OwnerInfo::fromRaw(...), N::list(N::child($raw, 'dbResults'), 'dbOwnerInfo'));
    }

    /** @deprecated Use isdsSearch3(). Raw response: totalCount, currentCount, position, lastPage, dbResults/dbResult. */
    public function isdsSearch2(string $text, ?string $searchType = null, ?string $scope = null, ?int $page = null, ?int $pageSize = null, ?bool $highlighting = null): \stdClass
    {
        return $this->call('ISDSSearch2', self::searchParams($text, $searchType, $scope, $page, $pageSize, $highlighting));
    }

    /**
     * Full-text search. Raw response: totalCount, currentCount, position, lastPage, dbResults/dbResult
     * (dbID, dbType, dbName, dbAddress, dbBiDate, dbICO, dbIdOVM, dbSendOptions).
     *
     * @param string|null $searchType GENERAL|ADDRESS|ICO|IDOVM|DBID
     * @param string|null $scope ALL|OVM|OVM_MAIN|…|FO
     */
    public function isdsSearch3(string $text, ?string $searchType = null, ?string $scope = null, ?int $page = null, ?int $pageSize = null, ?bool $highlighting = null): \stdClass
    {
        return $this->call('ISDSSearch3', self::searchParams($text, $searchType, $scope, $page, $pageSize, $highlighting));
    }

    /** dbState 1 = accessible, a message can be sent. */
    public function checkDataBox(string $dbID, ?bool $approveAsOvm = null, ?string $externRefNumber = null): DataBoxState
    {
        $params = ['dbID' => $dbID];
        if ($approveAsOvm !== null) {
            $params['dbApproved'] = $approveAsOvm;
        }
        if ($externRefNumber !== null) {
            $params['dbExternRefNumber'] = $externRefNumber;
        }
        $raw = $this->call('CheckDataBox', $params);
        return DataBoxState::fromRaw((object)['dbID' => $dbID, 'dbState' => N::int($raw, 'dbState')]);
    }

    public function getDataBoxActivityStatus(string $dbID, \DateTimeInterface $from, \DateTimeInterface $to): DataBoxState
    {
        return DataBoxState::fromRaw($this->call('GetDataBoxActivityStatus', [
            'dbID' => $dbID, 'baFrom' => N::toIsds($from), 'baTo' => N::toIsds($to),
        ]));
    }

    public function dataBoxCreditInfo(string $dbID, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): CreditInfo
    {
        $params = ['dbID' => $dbID];
        if ($from !== null) {
            $params['ciFromDate'] = $from->format('Y-m-d');
        }
        if ($to !== null) {
            $params['ciTodate'] = $to->format('Y-m-d'); // upstream spelling
        }
        return CreditInfo::fromRaw($this->call('DataBoxCreditInfo', $params));
    }

    /** Raw response: address elements + adRegistrationNumber, adFullAddress1 (one line), adFullAddress2 (lines split by "~"). */
    public function getDataBoxAddress(string $dbID): \stdClass
    {
        return $this->call('GetDataBoxAddress', ['dbID' => $dbID]);
    }

    /**
     * Raw response: dblData = the list file (zip). Public list types are ALL2 and POA (WS search manual 2.5).
     */
    public function getDataBoxList(string $dblType): \stdClass
    {
        return $this->call('GetDataBoxList', ['dblType' => $dblType]);
    }

    /** @param array<string, mixed> $dbOwnerInfo tdbPersonalOwnerInfo fields (pnFirstName, pnMiddleName, pnLastName, biDate, …) */
    public function findPersonalDataBox(array $dbOwnerInfo): \stdClass
    {
        return $this->call('FindPersonalDataBox', ['dbOwnerInfo' => $dbOwnerInfo]);
    }

    /** Raw response: dbPDZRecords/dbPDZRecord (PDZType O|G|K|E, PDZRecip, PDZPayer, PDZExpire, PDZCnt, ODZIdent). */
    public function pdzInfo(string $pdzSender): \stdClass
    {
        return $this->call('PDZInfo', ['PDZSender' => $pdzSender]);
    }

    /** @param string $pdzType Normal|Init|VoDZ — true when the box may send that kind of PDZ. */
    public function pdzSendInfo(string $dbId, string $pdzType): bool
    {
        return (bool)N::bool($this->call('PDZSendInfo', ['dbId' => $dbId, 'PDZType' => $pdzType]), 'PDZsiResult');
    }

    /** Data vault info. Raw response: ActDT* / FutDT* (type, capacity, from, to, used/paid). */
    public function dtInfo(string $dbId): \stdClass
    {
        return $this->call('DTInfo', ['dbId' => $dbId]);
    }

    /** Raw response: constRecords/constRecord (cName, cValue, cFrom, cTo). */
    public function getConstants(?\DateTimeInterface $date = null): \stdClass
    {
        return $this->call('GetConstants', $date === null ? [] : ['constDate' => $date->format('Y-m-d')]);
    }

    /** @return array<string, mixed> */
    private static function searchParams(string $text, ?string $type, ?string $scope, ?int $page, ?int $pageSize, ?bool $highlighting): array
    {
        return array_filter([
            'searchText' => $text, 'searchType' => $type, 'searchScope' => $scope,
            'page' => $page, 'pageSize' => $pageSize, 'highlighting' => $highlighting,
        ], static fn($v) => $v !== null);
    }
}
