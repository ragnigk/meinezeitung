<?php
  // XML to Array
  function xml2ary(&$string) {
      $parser = xml_parser_create();
      xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
      xml_parse_into_struct($parser, $string, $vals, $index);
      xml_parser_free($parser);

      $mnary=array();
      $ary=&$mnary;
      foreach ($vals as $r) {
          $t=$r['tag'];
          if ($r['type']=='open') {
              if (isset($ary[$t])) {
                  if (isset($ary[$t][0])) $ary[$t][]=array(); else $ary[$t]=array($ary[$t], array());
                  $cv=&$ary[$t][count($ary[$t])-1];
              } else $cv=&$ary[$t];
              if (isset($r['attributes'])) {foreach ($r['attributes'] as $k=>$v) $cv['_a'][$k]=$v;}
              $cv['_c']=array();
              $cv['_c']['_p']=&$ary;
              $ary=&$cv['_c'];

          } elseif ($r['type']=='complete') {
              if (isset($ary[$t])) { // same as open
                  if (isset($ary[$t][0])) $ary[$t][]=array(); else $ary[$t]=array($ary[$t], array());
                  $cv=&$ary[$t][count($ary[$t])-1];
              } else $cv=&$ary[$t];
              if (isset($r['attributes'])) {foreach ($r['attributes'] as $k=>$v) $cv['_a'][$k]=$v;}
              $cv['_v']=(isset($r['value']) ? $r['value'] : '');

          } elseif ($r['type']=='close') {
              $ary=&$ary['_p'];
          }
      }

      _del_p($mnary);
      return $mnary;
  }
  // _Internal: Remove recursion in result array
  function _del_p(&$ary) {
      foreach ($ary as $k=>$v) {
          if ($k==='_p') unset($ary[$k]);
          elseif (is_array($ary[$k])) _del_p($ary[$k]);
      }
  }


  $traueranzeigen = array();
  $old = getcwd();
  $quelle = shell_exec('ls *.tar.gz');
  shell_exec("tar xfvz $quelle");
  list($d,$rest) = explode("_",$quelle);
  chdir($d);
  foreach (glob("2*.pdf") as $filename) {
      shell_exec("pdftohtml -xml $filename");
      unlink($filename);
  }
  foreach (glob("*.xml") as $filename) {
      $tr = array();
      list($datum,$sap) = explode('__',basename($filename,'.xml'));
      $tr['datum'] = $datum;
      $tr['sap'] = $sap;
      //$a = json_decode(json_encode((array) simplexml_load_file($filename)),1);
      $a = xml2ary(file_get_contents($filename));
      foreach($a["pdf2xml"]["_c"] as $page){
          $tr['hoehe'] = $page["_a"]["height"];
          $tr['breite'] = $page["_a"]["width"];
          $size = 0;
          $id = 0;
          foreach($page["_c"]["fontspec"] as $fc){
              if((int)$fc["_a"]["size"] > $size){
                  $size = (int)$fc["_a"]["size"];
                  $id = (int) $fc["_a"]["id"];
              }
          }
          $desc = array();
          $trn = '';
          foreach($page["_c"]["text"] as $te){
              if(array_key_exists('_c', $te)){
                  $k = key($te['_c']);
                  $text = $te['_c'][$k]['_v'];
              } else if(array_key_exists('_v', $te)){
                  $text = $te['_v'];
              }
              if((int)$te['_a']["font"] == $id){
                  $trn .=  " ".$text;
              }
              $desc[(int)$te['_a']["top"]] = $text;
          }
          $tr['name'] = trim($trn);
          ksort($desc);
          $tr['anzeigentext'] = implode("\n",$desc);
      }
      $traueranzeigen[] = $tr;
      unlink($filename);
  }
  $xml = new SimpleXMLElement('<traueranzeigen/>');
  //var_dump($traueranzeigen);
  foreach($traueranzeigen as $tr){
      $trauer = $xml->addChild('anzeige');
      $trauer->addChild('datum',$tr['datum']);
      $trauer->addChild('sap',$tr['sap']);
      $trauer->addChild('hoehe',$tr['hoehe']);
      $trauer->addChild('breite',$tr['breite']);
      $trauer->addChild('name',$tr['name']);
      $trauer->addChild('anzeigentext',$tr['anzeigentext']);
  }
  $out = $xml->asXML();
  chdir($old);
  file_put_contents($d.'.xml',$out);
  rmdir($d);
?>