<?php
// make sure browsers see this page as utf-8 encoded HTML
include 'simple_html_dom.php';
include 'SpellCorrector.php';

header('Content-Type: text/html; charset=utf-8');
$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$didYouMean = null;
$results = false;


$csvdata = array_map('str_getcsvdata', file('URLtoHTML_mercury.csv'));
$urlmap = array();
for ($x = 1; $x < count($csvdata); $x++) {
  $urlmap["/home/shraddha/Desktop/mercurynews/mercurynews/".$csvdata[$x][0]] = $csvdata[$x][1];
}

if ($query)
{
 // The Apache Solr Client library should be on the include path
 // which is usually most easily accomplished by placing in the
 // same directory as this script ( . or current directory is a default
 // php include path entry in the php.ini)
 require_once('Apache/Solr/Service.php');
 // create a new solr service instance - host, port, and corename
 // path (all defaults in this example)
 $solr = new Apache_Solr_Service('localhost', 8983, '/solr/mycore/');
 // if magic quotes is enabled then stripslashes will be needed
 if (get_magic_quotes_gpc() == 1)
 {
 $query = stripslashes($query);
 }
 // in production code you'll always want to use a try /catch for any
 // possible exceptions emitted by searching (i.e. connection
 // problems or a query parsing error)
 try
 {

    
     $rank = $_GET['indexer'];
     if($rank == 'lucene'){
      $additional_parameters = array('sort' => '');;
     }else{
        $additional_parameters = array('sort' => 'pageRankFile desc');
     }
   $word = explode(" ",$query);

   $correctedQuery = array();
     for($i=0;$i<sizeOf($word);$i++){
      $correctedQuery[$i] = trim(SpellCorrector::correct($word[$i]));
       }
      $correctedQueryString = implode(" ", $correctedQuery);
     if(strtolower($query)==strtolower($correctedQueryString)){
        $results = $solr->search($correctedQueryString, 0, $limit, $additional_parameters);
     }
     else {
       $results = $solr->search($correctedQueryString, 0, $limit, $additional_parameters);
       $link = "http://localhost/solr-php-client/test.php?q=$correctedQueryString&indexer=$rank";
       $didYouMean = "Did you mean: <a href='$link'>$correctedQueryString</a>";
     }

 }
 catch (Exception $e)
 {
 // in production you'd probably log or email this error to an admin
 // and then show a special message to the user but for this example
 // we're going to show the full exception
 die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
 }
}
?>
<html>
 <head>
 <title>Search Engine</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
 </head>
 <body>
 <form accept-charset="utf-8" method="get">
 <label for="q">Search:</label>
 <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
 <div>
    <input type="radio" name="indexer" id="lucene" value="lucene" <?php if (isset($_GET['indexer']) && $_GET['indexer'] == 'lucene')  echo ' checked="checked"'; ?> <?php if(!isset($_GET['indexer'])) echo " checked=checked"?> >
    <label for="lucene">Lucene</label>
 </div>
  <div>
    <input type="radio" name="indexer" id="pagerank" value="pagerank" <?php if (isset($_GET['indexer']) && $_GET['indexer'] == 'pagerank')  echo ' checked="checked"'; ?> >
    <label for="pagerank">PageRank</label>
 </div>
 <input type="submit"/>
 </form>
<script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>
 <script
  src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"
  integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU="
  crossorigin="anonymous"></script>
  <script type="text/javascript">
$(function() {
	var count=0;
    var tags = [];
    var url = "http://localhost:8983/solr/mycore/suggest?q=";

     $("#q").autocomplete({
       source : function(request, response) {       
         var blank =  query.lastIndexOf(' ');
		 var query = $("#q").val().toLowerCase();
		 var word="";
		 var pretext="";
         if(query.length-1>blank && blank!=-1){
          word=query.substr(blank+1).trim();
          pretext = query.substr(0,blank).trim();
        }
        else{
          word=query.substr(0).trim(); 
        }
        console.log("word", word);
        var URL = url + word;
        console.log(URL)
        if(word && (blank != query.length-1)){
			
        $.ajax({
         url : URL,
         success : function(data) {
          var js =data.suggest.suggest;
          var docs = JSON.stringify(js);
          var jsonData = JSON.parse(docs);
          var result =jsonData[word].suggestions;
          querysuggestions = result.filter(function(value, index, arr){
              return value.term != word;
          });
          tags = []
          for(var i=0; i<querysuggestions.length; i++){
            tags.push(pretext+" "+querysuggestions[i].term);
          }
          response(tags);
        },
        dataType : 'jsonp',
        jsonp : 'json.wrf'
      });
  }
}});});
</script>
<?php
// display results
if ($results)
{
  if(isset($didYouMean)){
    echo $didYouMean;
  }
 $total = (int) $results->response->numFound;
 $start = min(1, $total);
 $end = min($limit, $total);
?>
 <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
 <ol>
<?php
function combinationUtil($words, $test, $start, $end, $index, $size, $lines) 
   { 
       $sub_snippet = "";
       if ($index == $size) 
       { 
           $text = "/";
		   $end_delim="\b)";
           $start_delim="(?=.*\b";
           for ($j=0; $j<$size; $j++) {
               $text=$text.$start_delim.$test[$j].$end_delim; 
           }
           $text=$text.".+/i";
           foreach($lines as $line){
          if (preg_match($text, $line) > 0){
            if(strlen($line) >= 160){
				$sub_snippet = substr($line, 0);
            }else{
              $sub_snippet = $line;
            }
            return $sub_snippet;
            }
        }
            
       } 

       for ($i=$start; $i<=$end && $end-$i+1 >= $size-$index; $i++) 
       { 
           $test[$index] = $words[$i]; 
           $temp = combinationUtil($words, $test, $i+1, $end, $index+1, $size, $lines); 
           if($temp != ""){
              return $temp;
           }
       } 
   } 

       $words = explode(" ", $query);
       $word_count = count($words);
       $test = array(); 
       $space_delim_query = implode("\\s*", $words);
       $new_query = "/(?:^|\C)".$space_delim_query."(?:$|\C)/i";
    

 foreach ($results->response->docs as $doc)
 {
      $html = str_get_html(file_get_contents($doc->id));
      $file_content = file_get_contents($doc->id);
      $html = str_get_html($file_content);
      $content =  ($html->plaintext);
      $snippet = "";
      if (preg_match($new_query, $content) > 0){
            if(strlen($content) > 160){
              $size = (160 - strlen($query))/2;
              $pos=strpos(strtolower($content), strtolower($query));
              $startpos = 0; 
          if($pos - $size > 0){
            $startpos = $pos - $size;
          }
          $snippet = substr($content, $startpos, 160);
            }
         }else{
            $start_delim="/(?=.*\b";
            $end_delim="\b)";
            foreach($words as $item){
              $text=$text.$start_delim.$item.$end_delim;
            }
            $text=$text.".+/i";         
          $flag = False; 
          $lines = explode(".", $content);
              foreach($lines as $line){
              if (preg_match($text, $line) > 0){
                if(strlen($line) >= 160){
              echo $line."<br>";
              $snippet = substr($line, 0);
                }else{
                  $snippet = $line;
                }
                $flag = True;
                break;
                }
            }
            if($flag == False){
              for($k = $word_count-1; $k>0; $k-- ){
              $snippet = combinationUtil($words, $test, 0, $word_count-1, 0, $k, $lines);
              if($snippet != ""){
                break;
              }
            }
            }
          }
      $snippet = html_entity_decode($snippet == "" ? "NA" : "...".$snippet."...");
?>
<li>
 <table style="border: 1px solid black; text-align: left; width: 100%">
<tr>
    <th style="width:20%">Title</th>
    <td>
      <?php 
        if($doc->title){
            ?> <a href="<?php echo htmlspecialchars($doc->og_url ? $doc->og_url : $urlmap[$doc->id], ENT_NOQUOTES, 'utf-8'); ?>" target="_blank">
              <?php echo htmlspecialchars($doc->title, ENT_NOQUOTES, 'utf-8'); ?>  
              </a>
        <?php }else{
            ?>NA<?php
        }
      ?>
    </td>
  </tr>
  <tr>
   <th style="width:20%">URL</th>
    <td>
      <a href="<?php  echo htmlspecialchars($doc->og_url ? $doc->og_url : $urlmap[$doc->id], ENT_NOQUOTES, 'utf-8');?>" target="_blank"><?php  echo htmlspecialchars($doc->og_url ? $doc->og_url : $urlmap[$doc->id], ENT_NOQUOTES, 'utf-8');?></a>
    </td>
  </tr>
<tr>
    <th style="width:20%">ID</th>
    <td>
      <?php
        if($doc->id){
            echo htmlspecialchars($doc->id, ENT_NOQUOTES, 'utf-8'); 
        }else{
            ?>NA<?php
        }
      ?>
    </td>
  </tr>
  <tr> 
    <th style="width:20%">Description</th>
    <td>
      <?php
        if($doc->og_description){
            echo htmlspecialchars($doc->og_description, ENT_NOQUOTES, 'utf-8');
        }else{
            ?>NA<?php
        }
      ?>
    </td>
  </tr>
  <tr> 
    <th style="width:20%">Snippet</th>
    <td>
      <?php
            echo htmlspecialchars($snippet, ENT_NOQUOTES, 'utf-8');
      ?>
    </td>
  </tr>
 </table>
 </li>
<?php
 }
?>
 </ol>
<?php
}
?>
 </body>
</html>