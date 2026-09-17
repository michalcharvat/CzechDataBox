<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Service;

/**
 * db_manipulations.wsdl — endpoint …/DS/DsManage. Box and user administration, mostly for data providers.
 * Thin wrappers: $params as the operation's WSDL input, the status-checked raw response is returned.
 */
final class Manipulations extends AbstractService
{
    /** @param array<string, mixed> $params */
    public function createDataBox(array $params): \stdClass
    {
        return $this->call('CreateDataBox', $params);
    }

    /** @param array<string, mixed> $params */
    public function createDataBox2(array $params): \stdClass
    {
        return $this->call('CreateDataBox2', $params);
    }

    /** @param array<string, mixed> $params */
    public function deleteDataBox(array $params): \stdClass
    {
        return $this->call('DeleteDataBox', $params);
    }

    /** @param array<string, mixed> $params */
    public function deleteDataBox2(array $params): \stdClass
    {
        return $this->call('DeleteDataBox2', $params);
    }

    /** @param array<string, mixed> $params */
    public function updateDataBoxDescr(array $params): \stdClass
    {
        return $this->call('UpdateDataBoxDescr', $params);
    }

    /** @param array<string, mixed> $params */
    public function updateDataBoxDescr2(array $params): \stdClass
    {
        return $this->call('UpdateDataBoxDescr2', $params);
    }

    /** @param array<string, mixed> $params */
    public function addDataBoxUser(array $params): \stdClass
    {
        return $this->call('AddDataBoxUser', $params);
    }

    /** @param array<string, mixed> $params */
    public function addDataBoxUser2(array $params): \stdClass
    {
        return $this->call('AddDataBoxUser2', $params);
    }

    /** @param array<string, mixed> $params */
    public function deleteDataBoxUser2(array $params): \stdClass
    {
        return $this->call('DeleteDataBoxUser2', $params);
    }

    /** @param array<string, mixed> $params */
    public function deleteDataBoxUser(array $params): \stdClass
    {
        return $this->call('DeleteDataBoxUser', $params);
    }

    /** @param array<string, mixed> $params */
    public function updateDataBoxUser(array $params): \stdClass
    {
        return $this->call('UpdateDataBoxUser', $params);
    }

    /** @param array<string, mixed> $params */
    public function updateDataBoxUser2(array $params): \stdClass
    {
        return $this->call('UpdateDataBoxUser2', $params);
    }

    /** @param array<string, mixed> $params */
    public function newAccessData(array $params): \stdClass
    {
        return $this->call('NewAccessData', $params);
    }

    /** @param array<string, mixed> $params */
    public function newAccessData2(array $params): \stdClass
    {
        return $this->call('NewAccessData2', $params);
    }

    /** @param array<string, mixed> $params */
    public function disableDataBoxExternally(array $params): \stdClass
    {
        return $this->call('DisableDataBoxExternally', $params);
    }

    /** @param array<string, mixed> $params */
    public function disableDataBoxExternally2(array $params): \stdClass
    {
        return $this->call('DisableDataBoxExternally2', $params);
    }

    /** @param array<string, mixed> $params */
    public function disableOwnDataBox(array $params): \stdClass
    {
        return $this->call('DisableOwnDataBox', $params);
    }

    /** @param array<string, mixed> $params */
    public function disableOwnDataBox2(array $params): \stdClass
    {
        return $this->call('DisableOwnDataBox2', $params);
    }

    /** @param array<string, mixed> $params */
    public function enableOwnDataBox(array $params): \stdClass
    {
        return $this->call('EnableOwnDataBox', $params);
    }

    /** @param array<string, mixed> $params */
    public function enableOwnDataBox2(array $params): \stdClass
    {
        return $this->call('EnableOwnDataBox2', $params);
    }

    /** @param array<string, mixed> $params */
    public function setOpenAddressing(array $params): \stdClass
    {
        return $this->call('SetOpenAddressing', $params);
    }

    /** @param array<string, mixed> $params */
    public function clearOpenAddressing(array $params): \stdClass
    {
        return $this->call('ClearOpenAddressing', $params);
    }

    /** @param array<string, mixed> $params */
    public function getDataBoxUsers2(array $params): \stdClass
    {
        return $this->call('GetDataBoxUsers2', $params);
    }
}
