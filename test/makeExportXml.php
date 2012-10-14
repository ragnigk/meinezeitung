<?php
  $traueranzeigen = array();
  foreach (glob("*.pdf") as $filename) {
      shell_exec("pdftohtml -xml $filename");
  }
  foreach (glob("*.xml") as $filename) {
      $tr = array();
      list($datum,$sap) = explode('__',basename($filename,'.xml'));
      $tr['datum'] = $datum;
      $tr['sap'] = $sap;
      $a = json_decode(json_encode((array) simplexml_load_file($filename)),1);
      foreach($a as $page){
          //var_dump($page);
          $tr['hoehe'] = $page["@attributes"]["height"];
          $tr['breite'] = $page["@attributes"]["width"];
          $size = 0;
          $id = 0;
          foreach($page["fontspec"] as $fc){
              if((int)$fc["@attributes"]["size"] > $size){
                  $size = (int)$fc["@attributes"]["size"];
                  $id = (int) $fc["@attributes"]["id"];
              }
          }
          $desc = '';
          $tr['name'] = '';
          foreach($page["text"] as $te){
              if(is_array($te)){
                  $t = array_values($te);
                  $text = $t[1];
              } else {
                  $text =  $te;
              }
              if(is_array($te) && (int)$te["@attributes"]["font"] == $id){
                  $tr['name'] .=  " ".$text;
              }
              $desc .= "\n".$text;
          }
          $tr['anzeigentext'] = $desc;
      }
      $traueranzeigen[] = $tr;
  }
  var_dump($traueranzeigen);
?>