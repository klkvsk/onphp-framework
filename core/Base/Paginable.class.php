<?php
/**
 * 
 * @author Соломонов Алексей <byorty@mail.ru>
 * @date 2015.05.07.05.15
 */

interface Paginable {

    public function setOffset($offet);
    public function setLimit($limit);
}