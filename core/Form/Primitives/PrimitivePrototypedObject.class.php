<?php

class PrimitivePrototypedObject extends BasePrimitive
{
    /** @var string|Prototyped */
    protected $className = null;
    /** @var Form */
    protected $form = null;

    /**
     * @param $className
     * @return $this
     */
    public function of($className)
    {
        assert(is_subclass_of($className, Prototyped::class));
        $this->className = $className;
        return $this;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function getProto()
    {
        $className = $this->getClassName();
        return $className::proto();
    }

    /**
     * @return Form
     */
    public function getInnerForm()
    {
        if (!$this->form) {
            $this->form = $this->getProto()->makeForm();
        }
        return $this->form;
    }

    /**
     * @throws WrongArgumentException
     * @return $this
     **/
    public function setValue($value)
    {
        Assert::isTrue($value instanceof $this->className);

        parent::setValue($value);

        return $this;
    }

    public function exportValue()
    {
        if (!$this->form)
            return null;

        return $this->form->export();
    }

    public function getInnerErrors()
    {
        return $this->getInnerForm()->getInnerErrors();
    }

    public function import($scope)
    {
        return $this->actualImport($scope, true);
    }

    public function unfilteredImport($scope)
    {
        return $this->actualImport($scope, false);
    }

    private function actualImport($scope, $importFiltering)
    {
        $form = $this->getInnerForm();

        if (!isset($scope[$this->name]))
            return null;

        $this->raw = $scope[$this->name];

        if (!$importFiltering) {
            $form
                ->disableImportFiltering()
                ->import($this->raw)
                ->enableImportFiltering();
        } else {
            $form->import($this->raw);
        }

        $this->imported = true;

        if ($form->getErrors())
            return false;

        $className = $this->getClassName();
        $this->value = new $className;

        FormUtils::form2object($form, $this->value, false);

        return true;
    }

    public function clean()
    {
        parent::clean();
        $this->form = null;
        return $this;
    }

}

?>