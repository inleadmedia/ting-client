<?php

class TingClientSearchRequest extends TingClientRequest {
  /**
   * Prefix to namespace URI map.
   */
  static $namespaces = array(
    '' => 'http://oss.dbc.dk/ns/opensearch',
    'xs' => 'http://www.w3.org/2001/XMLSchema',
    'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
    'oss' => 'http://oss.dbc.dk/ns/osstypes',
    'dc' => 'http://purl.org/dc/elements/1.1/',
    'dkabm' => 'http://biblstandard.dk/abm/namespace/dkabm/',
    'dcmitype' => 'http://purl.org/dc/dcmitype/',
    'dcterms' => 'http://purl.org/dc/terms/',
    'ac' => 'http://biblstandard.dk/ac/namespace/',
    'dkdcplus' => 'http://biblstandard.dk/abm/namespace/dkdcplus/',
    'marcx' => 'info:lc/xmlns/marcxchange-v1',
  );

  // Query parameter is required, so if not provided, we will just do a
  // wildcard search.
  protected $query = '*:*';
  protected $facets = array();
  protected $numFacets;
  protected $objectFormat;
  protected $start;
  protected $numResults;
  protected $rank;
  protected $sort;
  protected $allObjects;
  protected $allRelations;
  protected $relationData;
  protected $agency;
  protected $profile;
  protected $queryPrefix;
  protected $repository;
  protected $collectionType;
  var $userDefinedBoost;
  var $userDefinedRanking;

  public function getRequest() {
    $parameters = $this->getParameters();

    // These defaults are always needed.
    $this->setParameter('action', 'searchRequest');
    if (!isset($parameters['objectFormat']) || empty($parameters['objectFormat'])) {
      $this->setParameter('objectFormat', 'dkabm');
    }

    $methodParameterMap = array(
      'query' => 'query',
      'objectFormat' => 'objectFormat',
      'start' => 'start',
      'numResults' => 'stepValue',
      'rank' => 'rank',
      'sort' => 'sort',
      'allObjects' => 'allObjects',
      'allRelations' => 'allRelations',
      'relationData' => 'relationData',
      'agency' => 'agency',
      'profile' => 'profile',
      'repository' => 'repository',
    );

    foreach ($methodParameterMap as $method => $parameter) {
      $getter = 'get' . ucfirst($method);
      if ($value = $this->$getter()) {
        $this->setParameter($parameter, $value);
      }
    }

    // If we have facets to display, we need to construct an array of
    // them for SoapClient's benefit.
    $facets = $this->getFacets();
    if ($facets) {
      $this->setParameter('facets', array(
        'facetName' => $facets,
        'numberOfTerms' => $this->getNumFacets(),
      ));
    }

    // Include userDefinedBoost if set on the request.
    if (is_array($this->userDefinedBoost) && !empty($this->userDefinedBoost)) {
      $this->setParameter('userDefinedBoost', $this->userDefinedBoost);
    }

    // Include userDefinedRanking if set on the request.
    if (is_array($this->userDefinedRanking) && !empty($this->userDefinedRanking)) {
      $this->setParameter('userDefinedRanking', $this->userDefinedRanking);
    }

    // Include userDefinedRanking if set on the request.
    if (is_array($this->userDefinedRanking) && !empty($this->userDefinedRanking)) {
      $this->setParameter('userDefinedRanking', $this->userDefinedRanking);
    }

    // Include queryPrefix if set on request.
    if (!empty($this->getQueryPrefix())) {
      $this->setParameter('query', "{$this->getQueryPrefix()}={$this->getQuery()}");
    }

    return $this;
  }

  public function getQuery() {
    return $this->query;
  }

  public function setQuery($query) {
    $this->query = $query;
  }

  public function getFacets() {
    return $this->facets;
  }

  public function setFacets($facets) {
    $this->facets = $facets;
  }

  public function getNumFacets() {
    return $this->numFacets;
  }

  public function setNumFacets($numFacets) {
    $this->numFacets = $numFacets;
  }

  public function getObjectFormat() {
    return $this->objectFormat;
  }

  public function setObjectFormat($format) {
    $this->objectFormat = $format;
  }

  public function getStart() {
    return $this->start;
  }

  public function setStart($start) {
    $this->start = $start;
  }

  public function getNumResults() {
    return $this->numResults;
  }

  public function setNumResults($numResults) {
    $this->numResults = $numResults;
  }

  public function getRank() {
    return $this->rank;
  }

  public function setRank($rank) {
    $this->rank = $rank;
  }

  public function getSort() {
    return $this->sort;
  }

  public function setSort($sort) {
    $this->sort = $sort;
  }

  public function getAllObjects() {
    return $this->allObjects;
  }

  public function setAllObjects($allObjects) {
    $this->allObjects = $allObjects;
  }

  public function getAllRelations() {
    return $this->allRelations;
  }

  public function setAllRelations($allRelations) {
    $this->allRelations = $allRelations;
  }

  public function getRelationData() {
    return $this->relationData;
  }

  public function setRelationData($relationData) {
    $this->relationData = $relationData;
  }

  public function getAgency() {
    return $this->agency;
  }

  public function setAgency($agency) {
    $this->agency = $agency;
  }

  public function getProfile() {
    return $this->profile;
  }

  public function setProfile($profile) {
    $this->profile = $profile;
  }

  public function getQueryPrefix() {
    return $this->queryPrefix;
  }

  public function setQueryPrefix($queryPrefix) {
    $this->queryPrefix = $queryPrefix;
  }

  public function getRepository() {
    return $this->repository;
  }

  public function setRepository($repository) {
    $this->repository = $repository;
  }

  public function getCollectionType() {
    return $this->collectionType;
  }

  public function setCollectionType($collectionType) {
    $this->collectionType = $collectionType;
  }

  public function processResponse(stdClass $response) {
    $searchResult = new TingClientSearchResult();

    $searchResponse = $response->searchResponse;
    if (isset($searchResponse->error)) {
      throw new TingClientException('Error handling search request: '.self::getValue($searchResponse->error));
    }

    $searchResult->numTotalObjects = self::getValue($searchResponse->result->hitCount);
    $searchResult->numTotalCollections = self::getValue($searchResponse->result->collectionCount);
    $searchResult->more = (strcasecmp('true', self::getValue($searchResponse->result->more)) == 0);
    $searchResult->sortUsed = self::getValue($searchResponse->result->sortUsed);

    if (isset($searchResponse->result->searchResult) && is_array($searchResponse->result->searchResult)) {
      foreach ($searchResponse->result->searchResult as $entry => $result) {
        $searchResult->collections[] = $this->generateCollection($result->collection, (array)$response->{'@namespaces'});
      }
    }

    if (isset($searchResponse->result->facetResult->facet) && is_array($searchResponse->result->facetResult->facet)) {
      foreach ($searchResponse->result->facetResult->facet as $facetResult) {
        $facet = new TingClientFacetResult();
        $facet->name = self::getValue($facetResult->facetName);
        if (isset($facetResult->facetTerm)) {
          foreach ($facetResult->facetTerm as $term) {
            $facet->terms[self::getValue($term->term)] = self::getValue($term->frequence);
          }
        }

        $searchResult->facets[$facet->name] = $facet;
      }
    }

    return $searchResult;
  }

  private function generateObject($objectData, $namespaces) {
    $object = new TingClientObject();
    $object->id = self::getValue($objectData->identifier);
    $object->creationDate = self::getValue($objectData->creationDate);

    $object->record = array();
    $object->relations = array();
    $object->formatsAvailable = array();

    // The prefixes used in the response from the server may change over
    // time. We use our own map to provide a stable interface.
    $prefixes = array_flip(self::$namespaces);

    if (isset($objectData->record)) {
      foreach ($objectData->record as $name => $elements) {
        if (!is_array($elements)) {
          continue;
        }
        foreach ($elements as $element) {
          $namespace = $namespaces[isset($element->{'@'}) ? $element->{'@'} : '$'];
          $prefix = isset($prefixes[$namespace]) ? $prefixes[$namespace] : 'unknown';
          $key1 = $prefix . ':' . $name;
          if (isset($element->{'@type'}) && strpos($element->{'@type'}->{'$'}, ':') !== FALSE) {
            list($type_prefix, $type_name) = explode(':', $element->{'@type'}->{'$'}, 2);
            // This if statement checks if the namespace exists as trying to
            // access the array with a not defined namespace will give a PHP
            // notice error.
            $type_prefix_namespace = isset($type_prefix) ? $type_prefix : '$';
            if (isset($namespaces[$type_prefix_namespace])) {
              $type_namespace = $namespaces[$type_prefix_namespace];
              $type_prefix = isset($prefixes[$type_namespace]) ? $prefixes[$type_namespace] : 'unknown';
              $key2 = $type_prefix . ':' . $type_name;
            }
            else {
              $key2 = '';
              trigger_error('Undefined XML namespace (' . $type_prefix_namespace . ') in ' . __FILE__ . ' at ' . __LINE__, E_USER_NOTICE);
            }
          }
          else {
            $key2 = '';
          }
          if (!isset($object->record[$key1][$key2])) {
            $object->record[$key1][$key2] = array();
          }
          $object->record[$key1][$key2][] = $element->{'$'};
        }
      }
    }
    elseif ($this->objectFormat == 'marcxchange' && isset($objectData->collection->record)) {
      $records = $objectData->collection->record;
      if (!is_array($records)) {
        $records = array($records);
      }

      foreach ($records as $k => $record) {
        foreach ($record as $name => $element) {
          if ($name == '@') {
            continue;
          }
          if (strpos($name, '@') !== FALSE) {
            $key = str_replace('@', '', $name);
          }
          else {
            $key = $name;
          }

          if (is_array($element)) {
            if ($name == 'datafield') {
              $currentField = FALSE;
              foreach ($element as $key2 => $dataField) {
                if ($currentField != $dataField->{'@tag'}->{'$'}) {
                  $i = 0;
                }
                $currentField = $dataField->{'@tag'}->{'$'};
                $subFields = $dataField->subfield;
                if (!is_array($subFields)) {
                  $subFields = array($subFields);
                }


                foreach ($subFields as $subField) {
                  if (isset($subField->{'$'})) {
                    if (isset($object->record[$k][$key][$dataField->{'@tag'}->{'$'}][$i][$subField->{'@code'}->{'$'}])) {
                      $i++;
                    }

                    $object->record[$k][$key][$dataField->{'@tag'}->{'$'}][$i][$subField->{'@code'}->{'$'}] = $subField->{'$'};
                  }
                }

                $i++;
              }
            }
          }
          else {
            if (isset($element->{'$'})) {
              $object->record[$k][$key] = $element->{'$'};
            }
          }
        }
      }
    }

    if (!empty($object->record['ac:identifier'][''])) {
      list($object->localId, $object->ownerId) = explode('|', $object->record['ac:identifier'][''][0]);
    }
    else {
      list($object->ownerId, $object->localId) = explode(':', $object->id);
    }

    if (isset($objectData->relations)) {
      $object->relationsData = array();
      foreach ($objectData->relations->relation as $relation) {
        $relation_data = (object) array(
          'relationType' => $relation->relationType->{'$'},
          'relationUri' => $relation->relationUri->{'$'},
        );
        if (isset($relation->relationObject)) {
          $relation_object = $this->generateObject($relation->relationObject->object, $namespaces);
          $relation_data->relationObject = $relation_object;
          $relation_object->relationType = $relation_data->relationType;
          $relation_object->relationUri = $relation_data->relationUri;
          $object->relations[] = $relation_object;
        }
        $object->relationsData[] = $relation_data;
      }
    }

    if (isset($objectData->formatsAvailable)) {
      foreach ($objectData->formatsAvailable->format as $format) {
        $object->formatsAvailable[] = $format->{'$'};
      }
    }

    return $object;
  }

  private function generateCollection($collectionData, $namespaces) {
    $objects = array();
    if (isset($collectionData->object) && is_array($collectionData->object)) {
      foreach ($collectionData->object as $objectData) {
        $objects[] = $this->generateObject($objectData, $namespaces);
      }
    }
    return new TingClientObjectCollection($objects);
  }
}

