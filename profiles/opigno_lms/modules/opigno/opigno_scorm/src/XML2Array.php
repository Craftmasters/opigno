<?php

namespace Drupal\opigno_scorm;

/**
 * Class XML2Array.
 */
class XML2Array {

  private $output = [];
  private $xml_parser;
  private $xml_string;

  /**
   * Parse function.
   */
  public function parse($xlm_string) {
    $this->xml_parser = xml_parser_create();
    xml_set_object($this->xml_parser, $this);
    xml_set_element_handler($this->xml_parser, "tagOpen", "tagClosed");

    xml_set_character_data_handler($this->xml_parser, "tagData");

    $this->xml_string = xml_parse($this->xml_parser, $xlm_string);
    if (!$this->xml_string) {

    }

    xml_parser_free($this->xml_parser);

    return $this->output;
  }

  /**
   * Tag open function.
   */
  public function tagOpen($parser, $name, $attrs) {
    $tag = ["name" => $name, "attrs" => $attrs];
    array_push($this->output, $tag);
  }

  /**
   * Tag data function.
   */
  public function tagData($parser, $tagData) {
    if (trim($tagData)) {
      if (isset($this->output[count($this->output) - 1]['tagData'])) {
        $this->output[count($this->output) - 1]['tagData'] .= $tagData;
      }
      else {
        $this->output[count($this->output) - 1]['tagData'] = $tagData;
      }
    }
  }

  /**
   * Tag closed function.
   */
  public function tagClosed($parser, $name) {
    $this->output[count($this->output) - 2]['children'][] = $this->output[count($this->output) - 1];
    array_pop($this->output);
  }

}
