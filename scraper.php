<?
// This is a template for a PHP scraper on Morph (https://morph.io)
// including some code snippets below that you should find helpful

require 'scraperwiki.php';
require 'scraperwiki/simple_html_dom.php';
$url = "http://en.kingofsat.net/pos-19.2E.php";
//
// // Read in a page
$content = scraperwiki::scrape($url);
//
// // Find something on the page using css selectors
$html = new simple_html_dom();
$html->load($content);
$output = '';
$transponders = array();
$i =0;
$tbl1 = $html->find('table.frq');
foreach($tbl1 as $frq)
{
	$td = $frq->find('td');
	$satname = $td[1]->plaintext;
	$sat = $td[0]->plaintext;
	$id = $td[1]->find('img',0)->id;
	$id= substr($id, 1);
	$fre = (int) $td[2]->plaintext;
	$pola = $td[3]->plaintext;
	$tmp = $td[6]->plaintext;
	$arr = explode('-',$tmp);
	$type= $arr[1];
	$sym = $td[8]->find('a',0)->plaintext;	
	// if($pola !="10832")
	// continue;
$output = 'a0 = dvb_tune({
        adapter = 0,
        type = "'.$type.'",
        tp = "'.$fre.':'.$pola.':'.$sym.'",
        lnb = "9750:10600:11700",
        diseqc = 1,
})
';
$chl = "";
$r = array();
	$tbl2 = $html->find("div#$id table");
	foreach($tbl2 as $ch)
	{
		$chl = '';
		if(count($ch->find('tr')) >1)
		{
			
			foreach($ch->find('tr') as $tr)
			{
				$tdd = $tr->find('td');
				$cname= $tdd[2]->plaintext;
				$pnr = $tdd[7]->plaintext;
				$pack = $tdd[5]->find('a');
				$encrypt = $tdd[6]->plaintext;;
				$cpid = $tdd[8]->plaintext;;
				$hd = $tdd[8]->find('img',0);
				$cname = trim(preg_replace('/\s+/', ' ',$cname));
				if($cname =="")
					continue;
				if(strpos($cname,"HD") !== false)
					continue;
				if($hd)
					continue;
				if($cpid =="")
					continue;
					
				$cam = '';
				$tmp_r = array();
				if($encrypt !="Clear") {	
					
					foreach($pack as $p)
					{
						$port = recursive_array_search($p->plaintext,$satlist);
						if(!in_array($port,$r) && is_numeric($port))
							$r[] = $port;
						if(!in_array($port,$tmp_r) && is_numeric($port))
							$tmp_r[] = $port;
						// echo $cname.'--'.$port.'---'.$p->plaintext."<br />";
					}
						
					
					foreach($tmp_r as $val)
					{
						$key = array_search($val,$r);
						
							$cam .="&cam=r$key";
					}
				}
if(is_numeric($pnr) && count($tmp_r)>0 && is_numeric($cpid) ){
$chl .= '
make_channel({
        name = "'.trim(preg_replace('/\s+/', ' ',$cname)).'",
        input = { "dvb://a0#pnr='.$pnr.$cam.'" },
        output = { "udp://127.0.0.1:'.$pnr.'" },
})
';
}
			}
		}
	}
	$i++;
		$sat = preg_replace('/[^A-Za-z0-9\-\.]/', '', $sat);
	$sat = str_replace("deg","",$sat);

	// echo $sat."_".$fre.$pola ."------------<br />";
	// if($i == 3)
		// exit();
	$rl ='';
	foreach($r as $key=>$val)
	{
		$rl .= '
r'.$key.' = newcamd({
    name = "r'.$key.'",
    host = "server.com",
    port = '.$val.',
    user = "user",
    pass = "pass",
    key  = "0102030405060708091011121314",
})
';
	}
	
	if($chl =='')
		continue;
	$output.= $rl;
	$output.= $chl;
	// $fp = fopen($sat."_".$fre.$pola, 'w');
	// fwrite($fp, $output);
	// fclose($fp);	
	$title = $sat."_".$fre.$pola;
	scraperwiki::save_sqlite(array('title'), array('title' => $title, 'data' => $output));
	
}
										
function readlist($file)
{
	$file = fopen($file, "r");
	$words = array();
	$arr = array();
	while(!feof($file)){
		$line = fgets($file);
		$line = str_replace(array("\n", "\r"), '', $line);
		list($key,$val) = explode("\t", $line);
		$tmp = explode(";",$val);	
		if(count($tmp)>0)
			$arr[$key] = array_filter($tmp);
		else
			$arr[$key] = $val;
	}
	fclose($file);
	// $arr = array();
	
	return $arr;
}
function recursive_array_search($needle,$haystack) {
    foreach($haystack as $key=>$value) {
        $current_key=$key;
        if($needle===$value OR (is_array($value) && recursive_array_search($needle,$value) !== false)) 		 {
            return $current_key;
        }
    }
    return false;
}
// print_r($dom->find("table.list"));
//
// // Write out to the sqlite database using scraperwiki library
// scraperwiki::save_sqlite(array('name'), array('name' => 'susan', 'occupation' => 'software developer'));
//
// // An arbitrary query against the database
// scraperwiki::select("* from data where 'name'='peter'")

// You don't have to do things with the ScraperWiki library. You can use whatever is installed
// on Morph for PHP (See https://github.com/openaustralia/morph-docker-php) and all that matters
// is that your final data is written to an Sqlite database called data.sqlite in the current working directory which
// has at least a table called data.
?>
