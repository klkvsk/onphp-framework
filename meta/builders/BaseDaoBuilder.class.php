<?php

/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-07-12
 */
abstract class BaseDaoBuilder extends BaseBuilder
{
    /**
     * add documented proxy-methods to specify return type
     * @param MetaClass $class
     * @return string
     */
    protected static function buildGetters(MetaClass $class)
    {
        $out = '';

        $out .= "
            /**
             * @return {$class->getName()}
             */
            public function getById(\$id, \$expires = Cache::EXPIRES_MEDIUM)
            {
                return parent::getById(\$id, \$expires);
            }
        ";

        return $out;
    }

    protected static function buildPointers(MetaClass $class)
    {
        $out = '';

        if (!$class->getPattern() instanceof AbstractClassPattern) {
            if (
                $class->getIdentifier()->getColumnName() !== 'id'
            ) {
                $out .= "
                    public function getIdName()
                    {
                        return '{$class->getIdentifier()->getColumnName()}';
                    }
                    
                ";
            }

            $out .= "
                public function getTable()
                {
                    return '{$class->getTableName()}';
                }
                
                public function getObjectName()
                {
                    return {$class->getName()}::class;
                }
            ";

            if(
                $class->getIdentifier()->getType() instanceof UuidType
            )
            {
                $out .= "
                    public function getSequence()
                    {
                        return 'uuid';
                    }
                ";
            } else {
                $out .= "
                    public function getSequence()
                    {
                        return '{$class->getTableName()}_id';
                    }
                ";
            }

        } elseif ($class->getWithInternalProperties()) {
            $out .= "
                // no get{Table,ObjectName,Sequence} for abstract class
            ";
        }

        if ($liaisons = $class->getReferencingClasses()) {
            $uncachers = array();
            foreach ($liaisons as $className) {
                if( method_exists($className,'dao') ) {
                    $uncachers[] = $className.'::dao()->uncacheLists();';
                }
            }

            $uncachers = implode("\n", $uncachers);

            $out .= "
                public function uncacheLists()
                {
                    {$uncachers}
                
                    return parent::uncacheLists();
                }
            ";
        }

        if ($source = $class->getSourceLink()) {
            $out .= "
                public function getLinkName()
                {
                    return '{$source}';
                }
            ";
        }

        return $out;
    }

}