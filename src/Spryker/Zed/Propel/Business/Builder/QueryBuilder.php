<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Propel\Business\Builder;

use Propel\Generator\Builder\Om\QueryBuilder as PropelQueryBuilder;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\PropelTypes;

class QueryBuilder extends PropelQueryBuilder
{

    /**
     * @param \Propel\Generator\Model\Column $col
     *
     * @return string
     */
    protected function addFilterByColIn(Column $col)
    {
        $script = '';

        if ($col->isNumericType() || $col->isTemporalType() || $col->getType() == PropelTypes::ENUM || $col->isTextType()) {
            $colPhpName = $col->getPhpName();
            $variableName = $col->getCamelCaseName();
            $queryClassName = $this->getQueryClassName();

            $script .= <<<SCRIPT

    /**
     * Applies Criteria::IN filtering criteria for the column.
     *
     * @param array \$${variableName}s Filter value.
     *
     * @return \$this|$queryClassName The current query, for fluid interface
     */
    public function filterBy${colPhpName}_In(array \$${variableName}s)
    {
        return \$this->filterBy$colPhpName(\$${variableName}s, Criteria::IN);
    }

SCRIPT;
        }

        return $script;
    }

    protected function addFilterByColBetween(Column $col)
    {
        $script = '';

        if ($col->isNumericType() || $col->isTemporalType()) {
            $colPhpName = $col->getPhpName();
            $variableName = $col->getCamelCaseName();
            $queryClassName = $this->getQueryClassName();

            $script .= <<<SCRIPT

    /**
     * Applies SprykerCriteria::BETWEEN filtering criteria for the column.
     *
     * @param array \$$variableName Filter value.
     * [
     *    'min' => 3, 'max' => 5
     * ]
     *
     * 'min' and 'max' are optional, when neither is specified, throws \Spryker\Zed\Propel\Business\Exception\AmbiguousComparisonException.
     *
     * @return \$this|$queryClassName The current query, for fluid interface
     */
    public function filterBy${colPhpName}_Between(array \$$variableName)
    {
        return \$this->filterBy$colPhpName(\$$variableName, SprykerCriteria::BETWEEN);
    }

SCRIPT;
        }

        return $script;
    }

    /**
     * @param \Propel\Generator\Model\Column $col
     *
     * @return string
     */
    protected function addFilterByColLike(Column $col)
    {
        $script = '';

        if ($col->isTextType()) {
            $colPhpName = $col->getPhpName();
            $variableName = $col->getCamelCaseName();
            $queryClassName = $this->getQueryClassName();

            $script .= <<<SCRIPT

    /**
     * Applies SprykerCriteria::LIKE filtering criteria for the column.
     *
     * @param string \$$variableName Filter value.
     *
     * @return \$this|$queryClassName The current query, for fluid interface
     */
    public function filterBy${colPhpName}_Like(\$$variableName)
    {
        return \$this->filterBy$colPhpName(\$$variableName, Criteria::LIKE);
    }

SCRIPT;
        }

        return $script;
    }

    /**
     * @return array
     */
    protected function getAllowedArrayFilters()
    {
        return [
            'Criteria::IN',
            'Criteria::NOT_IN',
        ];
    }

    /**
     * Adds the filterByCol method for this object.
     *
     * @param string &$script The script will be modified in this method.
     * @param \Propel\Generator\Model\Column $col
     *
     * @return void
     */
    protected function addFilterByCol(&$script, Column $col)
    {
        $allowedArrayFilters = $this->getAllowedArrayFilters();
        $implodedArrayComparisons = implode(', ', $allowedArrayFilters);

        $this->declareClass('Spryker\\Zed\\Propel\\Business\\Exception\\AmbiguousComparisonException');
        $this->declareClass('Spryker\\Zed\\Propel\\Business\\Runtime\\ActiveQuery\\Criteria', 'Spryker');

        $colPhpName = $col->getPhpName();
        $colName = $col->getName();
        $variableName = $col->getCamelCaseName();
        $qualifiedName = $this->getColumnConstant($col);

        $script .= $this->addFilterByColBetween($col);
        $script .= $this->addFilterByColIn($col);
        $script .= $this->addFilterByColLike($col);

        $script .= "
    /**
     * Filter the query on the $colName column
     *";
        if ($col->isNumericType()) {
            $script .= "
     * Example usage:
     * <code>
     * \$query->filterBy$colPhpName(1234); // WHERE $colName = 1234
     * \$query->filterBy$colPhpName(array(12, 34), Criteria::IN); // WHERE $colName IN (12, 34)
     * \$query->filterBy$colPhpName(array('min' => 12), SprykerCriteria::BETWEEN); // WHERE $colName > 12
     * </code>";
            if ($col->isForeignKey()) {
                foreach ($col->getForeignKeys() as $fk) {
                    $script .= "
     *
     * @see       filterBy" . $this->getFKPhpNameAffix($fk) . "()";
                }
            }
            $script .= "
     *
     * @param     mixed \$$variableName The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent. Add Criteria::IN explicitly.
     *              Use associative array('min' => \$minValue, 'max' => \$maxValue) for intervals. Add SprykerCriteria::BETWEEN explicitly.";
        } elseif ($col->isTemporalType()) {
            $script .= "
     * Example usage:
     * <code>
     * \$query->filterBy$colPhpName('2011-03-14'); // WHERE $colName = '2011-03-14'
     * \$query->filterBy$colPhpName('now'); // WHERE $colName = '2011-03-14'
     * \$query->filterBy$colPhpName(array('max' => 'yesterday'), SprykerCriteria::BETWEEN); // WHERE $colName > '2011-03-13'
     * </code>
     *
     * @param     mixed \$$variableName The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent. Add Criteria::IN explicitly.
     *              Use associative array('min' => \$minValue, 'max' => \$maxValue) for intervals. Add SprykerCriteria::BETWEEN explicitly.";
        } elseif ($col->getType() == PropelTypes::PHP_ARRAY) {
            $script .= "
     * @param     array \$$variableName The values to use as filter. Use Criteria::LIKE to enable like matching of array values.";
        } elseif ($col->isTextType()) {
            $script .= "
     * Example usage:
     * <code>
     * \$query->filterBy$colPhpName('fooValue');   // WHERE $colName = 'fooValue'
     * \$query->filterBy$colPhpName('%fooValue%', Criteria::LIKE); // WHERE $colName LIKE '%fooValue%'
     * </code>
     *
     * @param     string \$$variableName The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE). Add Criteria::LIKE explicitly.";
        } elseif ($col->isBooleanType()) {
            $script .= "
     * Example usage:
     * <code>
     * \$query->filterBy$colPhpName(true); // WHERE $colName = true
     * \$query->filterBy$colPhpName('yes'); // WHERE $colName = true
     * </code>
     *
     * @param     boolean|string \$$variableName The value to use as filter.
     *              Non-boolean arguments are converted using the following rules:
     *                * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *                * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     *              Check on string values is case insensitive (so 'FaLsE' is seen as 'false').";
        } else {
            $script .= "
     * @param     mixed \$$variableName The value to use as filter";
        }
        $script .= "
     * @param     string \$comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return \$this|" . $this->getQueryClassName() . " The current query, for fluid interface
     *
     * @throws \\Spryker\\Zed\\Propel\\Business\\Exception\\AmbiguousComparisonException
     */
    public function filterBy$colPhpName(\$$variableName = null, \$comparison = Criteria::EQUAL)
    {";
        if ($col->isNumericType() || $col->isTemporalType()) {
            $script .= "

        if (is_array(\$$variableName)) {
            \$useMinMax = false;
            if (isset(\${$variableName}['min'])) {
                if (\$comparison != SprykerCriteria::BETWEEN && \$comparison != Criteria::GREATER_EQUAL) {
                    throw new AmbiguousComparisonException('\\'min\\' requires explicit Criteria::GREATER_EQUAL or SprykerCriteria::BETWEEN when \\'max\\' is also needed as comparison criteria.');
                }
                \$this->addUsingAlias($qualifiedName, \${$variableName}['min'], Criteria::GREATER_EQUAL);
                \$useMinMax = true;
            }
            if (isset(\${$variableName}['max'])) {
                if (\$comparison != SprykerCriteria::BETWEEN && \$comparison != Criteria::LESS_EQUAL) {
                    throw new AmbiguousComparisonException('\\'max\\' requires explicit Criteria::LESS_EQUAL or SprykerCriteria::BETWEEN when \\'min\\' is also needed as comparison criteria.');
                }
                \$this->addUsingAlias($qualifiedName, \${$variableName}['max'], Criteria::LESS_EQUAL);
                \$useMinMax = true;
            }
            if (\$useMinMax) {
                return \$this;
            }

            if (!in_array(\$comparison, [$implodedArrayComparisons])) {
                throw new AmbiguousComparisonException('\$$variableName of type array requires one of [$implodedArrayComparisons] as comparison criteria.');
            }
        }";
        } elseif ($col->getType() == PropelTypes::OBJECT) {
            $script .= "
        if (is_object(\$$variableName)) {
            \$$variableName = serialize(\$$variableName);
        }";
        } elseif ($col->getType() == PropelTypes::PHP_ARRAY) {
            $script .= "
        \$key = \$this->getAliasedColName($qualifiedName);
        if (null === \$comparison || \$comparison == Criteria::CONTAINS_ALL) {
            foreach (\$$variableName as \$value) {
                \$value = '%| ' . \$value . ' |%';
                if (\$this->containsKey(\$key)) {
                    \$this->addAnd(\$key, \$value, Criteria::LIKE);
                } else {
                    \$this->add(\$key, \$value, Criteria::LIKE);
                }
            }

            return \$this;
        } elseif (\$comparison == Criteria::CONTAINS_SOME) {
            foreach (\$$variableName as \$value) {
                \$value = '%| ' . \$value . ' |%';
                if (\$this->containsKey(\$key)) {
                    \$this->addOr(\$key, \$value, Criteria::LIKE);
                } else {
                    \$this->add(\$key, \$value, Criteria::LIKE);
                }
            }

            return \$this;
        } elseif (\$comparison == Criteria::CONTAINS_NONE) {
            foreach (\$$variableName as \$value) {
                \$value = '%| ' . \$value . ' |%';
                if (\$this->containsKey(\$key)) {
                    \$this->addAnd(\$key, \$value, Criteria::NOT_LIKE);
                } else {
                    \$this->add(\$key, \$value, Criteria::NOT_LIKE);
                }
            }
            \$this->addOr(\$key, null, Criteria::ISNULL);

            return \$this;
        }";
        } elseif ($col->getType() == PropelTypes::ENUM) {
            $script .= "
        \$valueSet = " . $this->getTableMapClassName() . "::getValueSet(" . $this->getColumnConstant($col) . ");
        if (is_scalar(\$$variableName)) {
            if (!in_array(\$$variableName, \$valueSet)) {
                throw new PropelException(sprintf('Value \"%s\" is not accepted in this enumerated column', \$$variableName));
            }
            \$$variableName = array_search(\$$variableName, \$valueSet);
        } elseif (is_array(\$$variableName)) {
            if (!in_array(\$comparison, [$implodedArrayComparisons])) {
                throw new AmbiguousComparisonException('array requires one of [$implodedArrayComparisons] as comparison criteria.');
            }
            \$convertedValues = array();
            foreach (\$$variableName as \$value) {
                if (!in_array(\$value, \$valueSet)) {
                    throw new PropelException(sprintf('Value \"%s\" is not accepted in this enumerated column', \$value));
                }
                \$convertedValues []= array_search(\$value, \$valueSet);
            }
            \$$variableName = \$convertedValues;
        }";
        } elseif ($col->isTextType()) {
            $script .= "
        if (\$comparison == Criteria::LIKE) {
            \$$variableName = str_replace('*', '%', \$$variableName);
        }

        if (is_array(\$$variableName) && !in_array(\$comparison, [$implodedArrayComparisons])) {
            throw new AmbiguousComparisonException('\$$variableName of type array requires one of [$implodedArrayComparisons] as comparison criteria.');
        }";
        } elseif ($col->isBooleanType()) {
            $script .= "
        if (is_string(\$$variableName)) {
            \$$variableName = in_array(strtolower(\$$variableName), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
        }";
        }
        $script .= "

        return \$this->addUsingAlias($qualifiedName, \$$variableName, \$comparison);
    }
";
    }

}
