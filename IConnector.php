<?php
namespace basebuy\basebuyAutoApi;


interface IConnector
{
    /**
     * @param string $method
     * @param string $format
     * @param array $params
     *
     * @return mixed
     */
    public function get($method, $format, $params = []);
}