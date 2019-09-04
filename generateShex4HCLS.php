<?php

$table = file_get_contents("table.html" );
$xml = "<?xml version='1.0' encoding='UTF-8'?>\n".$table;
$xml=simplexml_load_string($xml) or die("Error: Cannot create object");

foreach($xml->children() as $e => $o) { 
    if(isset($o->th)) continue;
    $a = (array) $o->td;
    if(count($a) !=6 ) continue; 
   # print_r($a);

    $obj = array();
    
    $obj['label'] = $a[0];
    $obj['predicate'] = $a[1];
    if(!is_object($a[2])) {
        $obj['value'] = $a[2];
    } else {
        $b = $o->td[2];
        $x = $b->saveXML();
        $obj['value'] = $x;
    }
    
    $summary = $a[3];
    $version = $a[4];
    $distribution = $a[5];
 
    $list['summary'][$summary][] = $obj;
    $list['version'][$version][] = $obj;
    $list['distribution'][$distribution][] = $obj;
    unset($obj);
}

$zeroOrOne = array(
 "dct:title", "dct:description","dct:created","dct:issued","pav:createdOn","pav:authoredOn","pav:curatedOn","dct:issue","schemaorg:logo","dct:license","dct:rights","idot:preferredPrefix","pav:version","pav:previousVersion","pav:hasCurrentVersion","dct:accrualPeriodicity","dcat:distribution"
);
$zeroOrOne = array_flip($zeroOrOne);

$prefix =
"PREFIX : <http://purl.org/hcls-metadata-spec/>
PREFIX cito: <http://purl.org/spar/cito/>
PREFIX dcat: <http://www.w3.org/ns/dcat#>
PREFIX dct: <http://purl.org/dc/terms/>
PREFIX dctypes: <http://purl.org/dc/dcmitype/>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX freq: <http://purl.org/cld/freq/>
PREFIX idot: <http://identifiers.org/idot/>
PREFIX lexvo: <http://lexvo.org/id/iso639-3/>
PREFIX pav: <http://purl.org/pav/>
PREFIX prov: <http://www.w3.org/ns/prov#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX schemaorg: <http://schema.org/>
PREFIX sd: <http://www.w3.org/ns/sparql-service-description#>
PREFIX sio: <http://semanticscience.org/resource/>
PREFIX void: <http://rdfs.org/ns/void#>
PREFIX void-ext: <http://ldf.fi/void-ext#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

";

$output = $prefix;

foreach($list AS $level => $a) {
    
    if($level == "summary") $extra = "EXTRA rdf:type";

    $output .= "<".ucfirst($level)."LevelShape> $extra {".PHP_EOL;

    foreach($a AS $requirement => $b) {
        $safe_requirement= str_replace(" ","-",$requirement);
#        if($safe_requirement === "MUST-NOT") continue;

        foreach($b AS $c) {
            $safe_value = $c['value'];
            $expression = '';
            if($c['predicate'] == "rdf:type") {
                if($level == "summary" && $safe_requirement == "MUST") $expression = ' rdf:type [dctypes:Dataset] ';
                if($level == "summary" && $safe_requirement == "MUST-NOT") $expression = ' rdf:type [void:Dataset dcat:Distribution] {0} ';
                if($level == "version" && $safe_requirement == "MUST") $expression = ' rdf:type [dctypes:Dataset] ';
                if($level == "version" && $safe_requirement == "MUST-NOT") $expression = ' rdf:type [void:Dataset dcat:Distribution] {0} ';
                if($level == "distribution" && $safe_requirement == "SHOULD") $expression = ' rdf:type [dcat:Distribution]? ';
                if($level == "distribution" && $safe_requirement == "MUST") $expression = ' rdf:type [void:Dataset dcat:Distribution] {2} ';
                $output .= " $expression ".PHP_EOL;
                $output .= '    // rdfs:comment "'.$level.' level '.$requirement.' use '. $c['predicate'].' with '.$safe_value.'"'.PHP_EOL;
                $output .= '    // :metadata-element "'.$c['label'].'"'.PHP_EOL;
                $output .= '    // :requirement-level :'.$safe_requirement.';'.PHP_EOL.PHP_EOL;

            } else { 
                if(isset($alreadyseen[$level][$safe_requirement][$c['predicate']])) continue;

                $predicates = explode(" or ", $c['predicate']);
                $value_type = $safe_value; 
                $value_restriction = '';
                $p = strrpos($safe_value, "[");
                if($p !== FALSE) {
                    $value_type = substr($safe_value, 0, $p);
                    $value_restriction = 'that is restricted to '.substr($safe_value, $p+1,-1);
                }

                foreach($predicates AS $predicate) {
                    $cardinality = '';
                    if($safe_requirement == "MUST-NOT") {
                        $expression = $predicate." . {0}";
                    } else {
                        $x = '';
                        if(($safe_requirement == "SHOULD") or ($safe_requirement == "MAY") or ($safe_requirement == "SHOULD-NOT")) {
                            $x = "*";
                            if(isset($zeroOrOne[$predicate])) $x = "?";
                        }
                        $expression = $predicate.' '.$value_type.$x;
                        if($x == "*") $cardinality = " zero or more ";
                        elseif($x == "?") $cardinality = " zero or one ";
                    }
                    

                
                    $output .= " $expression ".PHP_EOL;
                    $output .= '    // rdfs:comment "'.$level.' level '.$requirement.' use '. $predicate.' with'.$cardinality.$value_type.' '.$value_restriction.'"'.PHP_EOL;
                    $output .= '    // :metadata-element "'.$c['label'].'"'.PHP_EOL;
                    $output .= '    // :requirement-level :'.$safe_requirement.';'.PHP_EOL.PHP_EOL;                
                }
                $alreadyseen[$level][$safe_requirement][$c['predicate']] = TRUE;
                
            } 
        }
    }

    $output .= "}".PHP_EOL;
    

}

file_put_contents("hcls.shex",$output);

?>