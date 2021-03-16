<?php
class Travel
{
    const API_PATH = 'travels';

    /**
     * @param string $id
     */
    private string $id;

    /**
     * @param string $createdAt
     */
    private string $createdAt;

    /**
     * @param string $employeeName
     */
    private string $employeeName;

    /**
     * @param string $departure
     */
    private string $departure;

    /**
     * @param string $destination
     */
    private string $destination;

    /**
     * @param float $price
     */
    private float $price;

    /**
     * @param string $companyId
     */
    private string $companyId;

    /**
     * @param string $property
     * @return mixed
     */
    public function __get(string $property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    /**
     * @param string $property
     * @param mixed $value
     */
    public function __set(string $property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
    }
}
class Company
{
    const API_PATH = 'companies';

    /**
     * @param string $id
     */
    private string $id;

    /**
     * @param string $createdAt
     */
    private string $createdAt;

    /**
     * @param string $name
     */
    private string $name;

    /**
     * @param string $parentId
     */
    private string $parentId;

    /**
     * @var array
     */
    private array $children = [];

    /**
     * @param string $property
     * @return mixed
     */
    public function __get(string $property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    /**
     * @param string $property
     * @param mixed $value
     */
    public function __set(string $property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
    }
}

class TestScript
{
    const SERVICE_URL = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/';

    /**
     *
     */
    public function execute()
    {
        $start = microtime(true);

        $companies = $this->getCompanies();
        $travels = $this->getTravels();

        $travels = $this->mapDataToObjects(Travel::class, $travels);
        $companies = $this->mapDataToObjects(Company::class, $companies);
        $totalTravelsPrice = $this->getTotalTravelPrice($travels);

        $mappedCostsWithCompanies = $this->mapCosts($totalTravelsPrice, $companies);

        $result = $this->getCompaniesTree($mappedCostsWithCompanies);

        echo '<pre>';
        print_r($result);
        echo '</pre>';

        echo 'Total time: '.  (microtime(true) - $start);
    }

    /**
     * @param string $type
     * @return string
     */
    private function getResponse(string $type): string {
        return file_get_contents(self::SERVICE_URL . $type);
    }

    /**
     * @return array
     */
    private function getCompanies(): array {
        $response = $this->getResponse(Company::API_PATH);
        return $response ? json_decode($response, true) : [];
    }

    /**
     * @return array
     */
    private function getTravels(): array {
        $response = $this->getResponse(Travel::API_PATH);
        return $response ? json_decode($response, true) : [];
    }

    private function getTotalTravelPrice(array $travels)
    {
        $travelsTotal = [];
        foreach ($travels as $travel) {
            $travelsTotal[$travel->companyId] += $travel->price;
        }

        return $travelsTotal;
    }

    /**
     * @param string $objectName
     * @param array $data
     * @return array
     */
    private function mapDataToObjects(string $objectName, array $data): array
    {
        $result = [];
        if ($data) {
            foreach ($data as $datum) {
                $object = new $objectName();
                foreach ($datum as $key => $value) {
                    $object->__set($key, $value);
                }
                $result[] = $object;
            }
        }

        return $result;
    }

    /**
     * @param array $companies
     * @param array $travels
     * @param int $parentId
     * @return array
     */
    private function getCompaniesTree(array &$companies, $parentId = 0): array
    {
        $branch = [];

        foreach ($companies as $company) {
            if ($company['parentId'] == $parentId) {
                $company['children'] = [];
                $children = $this->getCompaniesTree($companies, $company['id']);
                if ($children) {
                    $company['children'] = $children;
                }
                unset($company['parentId']);
                $branch[] = $company;
            }
        }
        return $branch;
    }

    /**
     * @param array $totals
     * @param array $companies
     * @return array
     */
    private function mapCosts($totals, $companies): array
    {
        $result = [];
        foreach ($companies as $company) {
            $result[] = [
                'id' => $company->id,
                'cost' => $totals[$company->id],
                'name' => $company->name,
                'parentId' => $company->parentId,
            ];
        }

        return $result;
    }
}

(new TestScript())->execute();
