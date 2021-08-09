<?php
namespace HexMakina\Crudites;

trait TraitIntrospector
{
  public function search_and_execute_trait_methods($method_name)
  {
    $errors = [];
    $pattern = "Trait_$method_name"; // Trait Method must be correctly formatted
    // vd("SEARCHING FOR ***$pattern " . get_class($this));
    foreach((new \ReflectionClass($this))->getTraitNames() as $FQTraitName)
    {
      foreach((new \ReflectionClass($FQTraitName))->getMethods() as $method)
      {
        if(preg_match("/.+$pattern$/", $method->name, $match) === 1)
        {
          $callable = current($match);
          $this->$callable(); // TODO $res = ? what to do with eventual return data? errors, messages etc..
          // TODO handle errors in callable..
        }
      }
    }
    return $errors;
  }
}
