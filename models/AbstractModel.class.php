<?php
/**
 * TODO:
 */
abstract class AbstractModel {
	/**
	 * This function returns the name of the class it is modelling
	 */
	abstract function getName();
	
	/**
	 * Returns a dict with all keys and associated values for this array
	 * This is used for update queries.
	 */
	abstract function getKeyValueDict();
	
	/**
	 * This function should return the primary key of the used object.
	 */
	abstract function getPrimaryKey();
	
	/**
	 * This function should return the value of the primary key of the used object
	 */
	abstract function getPrimaryKeyValue();
	
	/**
	 * This function is used to set the id to the real databse value
	 */
	abstract function setId($id);
	
	/**
	 * this function returns the models id
	 */
	abstract function getId();
}

?>
