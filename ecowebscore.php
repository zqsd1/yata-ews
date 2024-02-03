<?php

add_action('admin_menu', 'ecowebscore_pages');
function ecowebscore_pages()
{
  add_menu_page(
    'ecoweb import', // Title
    'ecowebscore import', // intitulé lien
    'manage_options', // Capability requirement
    'ecoweb_import', // slug
    'ecowebscore_import_hmtl' // callback function

  );
  //import
  add_submenu_page(
    'ecoweb_import',
    'ecowebscore import',
    'manage_options',
    'ecoweb_import',
    'ecowebscore_import_hmtl'
  );
  //export
  add_submenu_page(
    'ecoweb_import',
    'ecoweb export',
    'ecowebscore export',
    'manage_options',
    'ecoweb_export',
    'ecowebscore_export_html'
  );
  //add cat
  add_submenu_page(
    'ecoweb_import',
    'ecoweb create category',
    'ecowebscore create cat',
    'manage_options',
    'ecoweb_create_cat',
    'ecowebscore_create_cat_html'
  );
  //list projects
  add_submenu_page(
    'ecoweb_import',
    'ecoweb list project',
    'ecowebscore projets list',
    'manage_options',
    'ecoweb_project_list',
    'ecowebscore_project_list_html'
  );
}
add_action('admin_enqueue_scripts', 'ecoweb_js');
function ecoweb_js()
{
  $current_screen_id = get_current_screen()->id;
  if (
    $current_screen_id === "toplevel_page_ecoweb_import" ||
    $current_screen_id === "ecowebscore-import_page_ecoweb_export" ||
    $current_screen_id === "ecowebscore-import_page_ecoweb_create_cat" ||
    $current_screen_id === "ecowebscore-import_page_ecoweb_project_list"
  ) {


    wp_enqueue_script("select2", "https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js");
    wp_enqueue_style("select2", "https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css");

    wp_enqueue_script('ecowebscore-js', '/wp-content/themes/yatatheme/js/ecowebscore.js', '', '', ['strategy' => 'defer']);
    wp_enqueue_style("mycss", "/wp-content/themes/yatatheme/css/ews-api/index.php");
  }
}

///////
//PAGES HTML
///////
function ecowebscore_import_hmtl()
{
?>
  <div class="wrap wp-core-ui">
    <div class="container-ecowebscore">
      <h3>Bulk import</h3>

      <form action="<?php menu_page_url('ecoweb_import') ?>" method="post" enctype="multipart/form-data">

        <label for="fichier" class="button">choisir</label>
        <input class="" type="file" name="fichier" id="fichier">

        <button class="button-primary" type="submit" name="submit" value="import_from_csv">upload</button>
      </form>
    </div>
    </br>
    <div class="container-ecowebscore">
      <h3>créer projet</h3>
      <form action="" method="post">

        <?php select_category_html(true); ?>
        <label for="libelle">libelle:</label>
        <input type="text" name="libelle" id="libelle" class="" required>


        <label for="projectUrl">url:</label>
        <input type="url" name="projectUrl" id="projectUrl" class="" required>

        <label for="projectInfos">infos:</label>
        <input type="text" name="projectInfos" id="projectInfos" class="">

        <button class="button-primary" type="submit" class="" name="submit" value="create_projet">OK</button>
      </form>
    </div>
  </div>
<?php
}

function ecowebscore_export_html()
{
?>
  <div class="wrap wp-core-ui">
    <div class="container-ecowebscore">
      <h3>projects from old API</h3>
      <form action="" method="post">
        <label for="category">categorie :</label>
        <input type="number" name="category" id="category">
        <button class="button-primary" type="submit" name="submit" value="export_to_csv">download</button>
      </form>
    </div>
    <div class="container-ecowebscore">
      <h3>projects from new API</h3>
      <form action="" method="post">
        <?php select_category_html(true) ?>

        <button class="button-primary" type="submit" name="submit" value="export_new_api_tocsv">download</button>
      </form>
    </div>
  </div>

<?php
}

function ecowebscore_create_cat_html()
{
  //recup les categories
  $result = request_api("http://api.ecowebscore.com/api/v1/categories", "GET");
  $categories = json_decode($result)->data;
?>
  <div class="wrap wp-core-ui">
    <div class="container-ecowebscore">
      <?php
      search_input_html();
      table_header_html(['id', 'name', 'parentId']);
      foreach ($categories as $category) {
        table_line_html([$category->id, $category->name, $category->parentId]);
      }
      echo "</table>";
      ?>
    </div>
    <div class="container-ecowebscore">

      <form action="" method="post">
        <label for="cat_name">nom :</label>
        <input type="text" name="cat_name" id="cat_name">
        <?php select_category_html(); ?>


        <button class="button-primary" type="submit" name="submit" value="add_category">creer category</button>
      </form>
    </div>
  </div>
<?php
}

function ecowebscore_project_list_html()
{
  $project_detail = test_positive_int($_GET['project_detail'] ?? "");
  $page = test_positive_int($_GET['projets_page'] ?? 1) ?? 1;
  $url_choosed = test_url($_GET['project_url']);

  if ($project_detail !== null) {
    if ($url_choosed !== null) detail_url_project_html($page, $project_detail, $url_choosed);
    else detail_project_html($page, $project_detail);
  } else
    list_projects_html($page);
}

function list_projects_html($page)
{
  $url = "http://api.ecowebscore.com/api/v1/projects?page=" . $page;

  $response = request_api($url, "GET");
  $projects = json_decode($response)->data;

  //pages navigation
  $pages = json_decode($response)->meta;
  $navigation = "";
  $backward = esc_url(add_query_arg('projets_page', ($pages->current_page - 1), menu_page_url("ecoweb_project_list", false)));
  $forward = esc_url(add_query_arg('projets_page', ($pages->current_page + 1), menu_page_url("ecoweb_project_list", false)));
  if ($pages->current_page > 1) $navigation .= "<a href=$backward class='button btn-nav-ecoweb'>  <  </a>";
  if ($pages->current_page < $pages->last_page) $navigation .= "<a href=$forward class='button btn-nav-ecoweb'>  >  </a>";
  //////////

  echo "<div class='wrap wp-core-ui'>";
  search_input_html();
  echo '<div class="container-ecowebscore">';
  table_header_html(['id', 'URL', 'libelle', 'Infos', 'noteMobile', 'noteDesktop', 'ecowebscoreMobile', 'ecowebscoreDesktop', 'productKey', 'detail']);
  foreach ($projects as $project) {
    $detail_projet = esc_url(add_query_arg(['project_detail' => $project->id, 'projets_page' => $pages->current_page], menu_page_url("ecoweb_project_list", false)));
    table_line_html([
      $project->id,
      $project->projectUrl,
      $project->libelle,
      $project->projectInfos,
      $project->noteMobile,
      $project->noteDesktop,
      $project->ecoWebScoreMobile,
      $project->ecoWebScoreDesktop,
      $project->productKey,
      "<a href= $detail_projet class='button'>détail</a>"
    ]);
  }
  echo "</table>";
  echo "</div>";
  echo $navigation;
  echo "</div>";
}
//TODO project detail export en csv toute les url tout a la derniere date
// url + note desktop/ note mobile +note sur 10 

//TODO reanalise la page POST datas
function detail_project_html($page, $project_detail)
{
  $response = request_api("http://api.ecowebscore.com/api/v1/projects/$project_detail?includeCategory=true&includeDatas=true", "GET");
  $project = json_decode($response)->data;

  $backlink = esc_url(add_query_arg(['projets_page' => $page], menu_page_url("ecoweb_project_list", false)));
?>
  <div class="wrap wp-core-ui">
    <div style="display: flex;">

      <?php backlink_btn($backlink) ?>

      <form action="" method="post" class="export-csv-btn">
        <input type="hidden" name="projectId" value=<?= $project_detail ?>>
        <button class="button-primary" type="submit" name="submit" value="download_project_csv">download csv</button>
      </form>
    </div>

    <h2><?= $project->libelle ?></h2>
    <a href=<?= $project->projectUrl ?>><?= $project->projectUrl ?></a>
    </br>
    categorie : <?= $project->category->id ?> : <?= $project->category->name ?>
    </br>

    <?php
    search_input_html();
    echo '<div class="container-ecowebscore">';
    table_header_html(['url', 'ecowebMobile', 'noteMobile', 'updatedMobile', 'ecowebDesktop', 'noteDesktop', 'updatedDesktop', 'reUpdate']);

    //list uniques url
    $url_uniques = array_url_uniques($project->datas);

    foreach ($url_uniques as $value) {
      $reduced_desktop = last_updated_by_strategy($value->datas, "DESKTOP");
      $reduced_mobile = last_updated_by_strategy($value->datas, "MOBILE");

      $url_history = esc_url(add_query_arg(['project_url' => $value->url, 'project_detail' => $project->id, 'projets_page' => $page], menu_page_url("ecoweb_project_list", false)));

      $btn_update = '
        <form action="" method="post">
          <input type="hidden" name="url"value=' . $value->url . '>
          <button class="button" type="submit" name="submit" value="request_update_url_data">update</button>
        </form>';

      table_line_html([
        "<a href=$url_history>$value->url</a>",
        $reduced_mobile->ecoWebScore,
        $reduced_mobile->note,
        $reduced_mobile->updated,
        $reduced_desktop->ecoWebScore,
        $reduced_desktop->note,
        $reduced_desktop->updated,
        $btn_update
      ]);
    }
    echo  "</table>";
    echo "</div>";
    echo "</div>";
  }

  function detail_url_project_html($page, $project_detail, $url_choosed)
  {

    $response = request_api("http://api.ecowebscore.com/api/v1/projects/$project_detail?includeCategory=true&includeDatas=true", "GET");
    $project = json_decode($response)->data;

    $backlink = esc_url(add_query_arg(['project_detail' => $project->id, 'projets_page' => $page], menu_page_url("ecoweb_project_list", false)));

    ?>
    <div class="wrap wp-core-ui">
      <?php backlink_btn($backlink) ?>
      <div class="container-ecowebscore">
        <h3><?= $url_choosed ?></h3>
      </div>
      <div class="container-ecowebscore">
        <h4>datas :</h4>
        <?php
        search_input_html();
        table_header_html(['id', 'domSize', 'totalByteWeight', 'numberRequests', 'note', 'ecoWebScore', 'strategy', 'state', 'updated']);
        foreach ($project->datas as $data) {
          if ($data->pageUrl !== $url_choosed) continue;
          table_line_html([
            $data->id,
            $data->domSize,
            $data->totalByteWeight,
            $data->numberRequests,
            $data->note,
            $data->ecoWebScore,
            $data->strategy,
            $data->state,
            $data->updated
          ]);
        }
        echo "</table>";

        echo "</div>";
        echo "</div>";
      }

      ///////////
      //POST
      ////////////
      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        switch (test_input($_POST["submit"] ?? "")) {
          case 'add_category':
            //get & clean form values
            $category_name = test_input($_POST['cat_name'] ?? "");
            if ($category_name == '') pppd('no cat name provided');
            $parent = test_positive_int($_POST['parent'] ?? "");

            //test si parent exist
            if ($parent !== null)
              if (!is_category_exists($parent)) pppd('parent category existe pas');
            add_category($category_name, $parent);
            break;

          case 'export_to_csv':

            $category_id = test_positive_int($_POST['category'] ?? "");
            if ($category_id === null) pppd("pas de catégorie passé");
            export_csv($category_id);
            break;

          case 'create_projet':
            $categoryId = test_positive_int($_POST['categoryId'] ?? "");
            if ($categoryId === null) pppd('choose a category');
            if (!is_category_exists($categoryId)) pppd('category existe pas');

            $libelle = test_input($_POST['libelle'] ?? "");
            if ($libelle === "") pppd("pas de libelle fourni");

            $projectUrl = test_url($_POST['projectUrl'] ?? "");
            if ($projectUrl === null) pppd("pas d'url valide");

            $projectInfos = test_input($_POST['projectInfos'] ?? "");

            import($categoryId, $libelle, $projectUrl, $projectInfos);
            break;

          case 'import_from_csv':
            //test exit
            //test extension
            if (!file_exists($_FILES["fichier"]["tmp_name"])) pppd('no file given');
            //TODO solve problem csv == text/plain
            //  if (mime_content_type($_FILES["fichier"]["tmp_name"]) != "text/csv" || mime_content_type($_FILES["fichier"]["tmp_name"] != "text/plain")) pppd('not a csv file');
            import_bulk($_FILES["fichier"]["tmp_name"]);
            break;

          case 'export_new_api_tocsv':
            $category_id = test_positive_int($_POST['categoryId'] ?? "");
            if ($category_id === null) pppd('category number ?');
            export_new_api_tocsv($category_id);
            break;

          case 'request_update_url_data':
            $url = test_url($_POST['url']);
            if ($url === null) pppd("invalid url");

            $response = request_api("http://api.ecowebscore.com/api/v1/datas", "POST", json_encode(['pageUrl' => $url], JSON_UNESCAPED_UNICODE));
            popup($response);
            break;

          case 'download_project_csv':
            $project_id = test_positive_int($_POST['projectId']);
            if ($project_id === null) pppd('fail');

            $response = request_api("http://api.ecowebscore.com/api/v1/projects/$project_id?includeCategory=true&includeDatas=true", "GET");
            $project = json_decode($response)->data;
            //list uniques url
            $url_uniques = array_url_uniques($project->datas);

            $csvfile = export_project_csv($url_uniques);
            download_csv($csvfile);
            break;

          default:
            # code...
            break;
        }
      }

      /////////
      //TEST CHAMPS FORM
      ////////
      function test_input($data)
      {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
      }

      function test_positive_int($int)
      {
        $result = filter_var($int, FILTER_SANITIZE_NUMBER_INT);
        if ($result === false)
          return null;
        $is_positive = filter_var($result, FILTER_VALIDATE_INT, array("options" => array("min_range" => 0)));
        if (!$is_positive)
          return null;
        return $result;
      }
      function test_url($url)
      {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
      }

      ////////
      //PRINT HTML
      ////////
      function table_header_html(array $champs)
      {
        ?>
        <table class="table-sort-js">
          <tr>
            <?php for ($i = 0; $i < sizeof($champs); $i++) : ?>
              <th><?= $champs[$i] ?></th>
            <?php endfor; ?>
          </tr>
        <?php
      }

      function table_line_html(array $champs)
      {
        ?>
          <tr>
            <?php for ($i = 0; $i < sizeof($champs); $i++) : ?>
              <td><?= $champs[$i] ?></td>
            <?php endfor; ?>
          </tr>
        <?php
      }

      function backlink_btn($backlink)
      {
        ?>
          <a href=<?= $backlink ?> class='page-title-action'>return</a>
        <?php
      }

      function select_category_html(bool $isRequired = false)
      {
        $required = $isRequired ? "required" : "";
        $response = request_api("http://api.ecowebscore.com/api/v1/categories", "GET");
        $categories = json_decode($response)->data;

        $cat_options = "";
        for ($i = 0; $i < sizeof($categories); $i++) {
          $cat_options .= "<option value={$categories[$i]->id}>{$categories[$i]->id}: {$categories[$i]->name}</option>";
        }
        ?>
          <label for="categoryId">category:</label>
          <select class="select2-js" name="categoryId" id="categoryId" <?= $required ?>>
            <option disabled selected value> -- select an option -- </option>
            <?= $cat_options ?>
          </select>
        <?php
      }

      function search_input_html()
      {
        ?>
          <input type="text" name="search-table" class="search-input-js" id="search-table" placeholder="chercher dans la table">
        <?php
      }

      /////////
      //Utility
      /////////
      function is_category_exists($category)
      {
        $result = request_api("http://api.ecowebscore.com/api/v1/categories/$category", "GET");
        return isset(json_decode($result)->data);
      }

      function array_url_uniques($array)
      {
        return array_reduce($array, function ($carry, $item) {
          $transformed = new stdClass();
          $transformed->url = $item->pageUrl;
          unset($item->pageUrl);

          foreach ($carry as $url) {
            if ($url->url === $transformed->url) {
              array_push($url->datas, $item);
              return $carry;
            }
          }
          $transformed->datas = [$item];
          array_push($carry, $transformed);
          return $carry;
        }, []);
      }

      function last_updated_by_strategy(array $array, string $strategy)
      {

        return array_reduce($array, function ($carry, $item) use ($strategy) {
          if ($item->strategy !== $strategy) return $carry;
          if ($carry == null) return $item;
          if (strtotime($carry->updated) > strtotime($item->updated))
            return $carry;
          return $item;
        });
      }

      function add_category($cat_name, $parent)
      {
        $json_data = new stdClass();
        $json_data->name = $cat_name;
        $json_data->parentId = $parent;
        $response = request_api("http://api.ecowebscore.com/api/v1/categories", "POST", json_encode($json_data, JSON_UNESCAPED_UNICODE));
        popup($response);
      }

      function import($categoryId, $libelle, $projectUrl, $projectInfos)
      {
        $project = new stdClass();
        $project->userId = 1;
        $project->categoryId = $categoryId; //filter_input(INPUT_POST, 'categoryId', FILTER_VALIDATE_INT);
        $project->projectUrl = $projectUrl;
        $project->isActive = true;
        $project->isFree = false;
        $project->barometre = false;
        $project->greenHost = true;
        $project->libelle = $libelle;
        $project->projectInfos = $projectInfos;
        $response = request_api("http://api.ecowebscore.com/api/v1/projects", "POST", json_encode($project));
        popup($response);
      }

      function import_bulk($file)
      {
        $handle = fopen($file, 'r');

        if ($handle === false)  exit("Error opening CSV");

        $titles = fgetcsv($handle, null, ";");

        $results = [];

        while (($csvline = fgetcsv($handle, null, ";")) !== false) {

          $project = [];
          for ($i = 0; $i < count($titles); $i++) {
            $project[$titles[$i]] = $csvline[$i];
          }

          $project['isActive'] = true;
          $project['isFree'] = true;
          $project['barometre'] = true;
          $project['greenHost'] = false;
          $project['userId'] = 1;

          array_push($results, $project);
        }

        fclose($handle);

        $response = request_api("http://api.ecowebscore.com/api/v1/projects/bulk", "POST", json_encode($results, JSON_UNESCAPED_UNICODE));

        popup($response);
      }

      function export_new_api_tocsv($idcateg)
      {

        header('Content-Type: text/csv');
        $csvfile = "./ecoweb-export.csv";

        $response = request_api("http://api.ecowebscore.com/api/v1/categories/$idcateg?includeProjects=true", "GET");
        $projects = json_decode($response)->data->projects;

        $handle = fopen($csvfile, 'w');
        if ($handle === false)  exit("Error creating CSV");
        $titres = array("projectUrl", "libelle", "categoryId", "projectInfos");
        fputcsv($handle, $titres, ';');

        foreach ($projects as $project) {
          $result = ["", "", "", ""];
          $result[0] = $project->projectUrl;
          $result[1] = $project->libelle;
          $result[2] = $idcateg;
          $result[3] = $project->projectInfos;

          fputcsv($handle, $result, ';');
        }
        fclose($handle);

        download_csv($csvfile);
      }

      function export_project_csv($url_uniques)
      {
        header('Content-Type: text/csv');
        $csvfile = "./ecoweb-project-export.csv";

        $handle = fopen($csvfile, 'w');
        if ($handle === false)  exit("Error creating CSV");
        $titres = array(
          'url',
          'ecoWebScore',
          'note',
          'updated',
          'domSize',
          'totalByteWeight',
          'numberRequests',
          'strategy'

        );
        fputcsv($handle, $titres, ';');
        foreach ($url_uniques as $value) {
          $reduced_desktop = last_updated_by_strategy($value->datas, "DESKTOP");
          $reduced_mobile = last_updated_by_strategy($value->datas, "MOBILE");

          $line1 = [
            $value->url,
            $reduced_mobile->ecoWebScore,
            $reduced_mobile->note,
            $reduced_mobile->updated,
            $reduced_mobile->domSize,
            $reduced_mobile->totalByteWeight,
            $reduced_mobile->numberRequests,
            "MOBILE" // $reduced_mobile->strategy
          ];
          $line2 = [
            $value->url,
            $reduced_desktop->ecoWebScore,
            $reduced_desktop->note,
            $reduced_desktop->updated,
            $reduced_desktop->domSize,
            $reduced_desktop->totalByteWeight,
            $reduced_desktop->numberRequests,
            "DESKTOP" //$reduced_desktop->strategy
          ];
          fputcsv($handle, $line1, ';');
          fputcsv($handle, $line2, ';');
        }
        fclose($handle);
        return $csvfile;
      }

      function export_csv($idcateg)
      {
        header('Content-Type: text/csv');
        $csvfile = "./ecoweb-export.csv";

        $response = request_old_api($idcateg);
        $jsonprojects = json_decode($response, true);


        $handle = fopen($csvfile, 'w');
        if ($handle === false)  exit("Error creating CSV");
        $titres = array("projectUrl", "libelle", "categoryId", "projectInfos");
        fputcsv($handle, $titres, ';');


        foreach ($jsonprojects as $project) {
          $result = ["", "", "", ""];
          foreach ($project as $key => $value) {
            switch ($key) {
              case 'project_url':
                $result[0] = $value;
                break;
              case 'libelle':
                $result[1] = $value;
                break;
              case 'category_id':
                $result[2] = $idcateg;
                break;
              case 'project_infos':
                $result[3] = $value;
                break;

              default:
                # code...
                break;
            }
          }
          fputcsv($handle, $result, ';');
        }
        fclose($handle);

        download_csv($csvfile);
      }

      function download_csv($file)
      {
        header("Content-Type: text/csv");
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename=' . basename($file));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
      }

      function request_old_api($idcateg)
      {
        $authorization = "aA5K5BbKn1fCRFTVW2pVSJE87PjGgAdz";
        $buildQuery = "orderby=note_desktop";
        $ch1 = curl_init('https://ecowebscore.com/api/eco-api/v1/projectsbycat/' . $idcateg . '?' . $buildQuery);
        curl_setopt($ch1, CURLOPT_HTTPHEADER, array('Content-Type: application/json', "Authorization: $authorization"));
        curl_setopt($ch1, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_FOLLOWLOCATION, 1);

        $response = curl_exec($ch1);
        $err = curl_error($ch1);

        curl_close($ch1);

        if ($err) {
          exit("cURL Error #:" . $err);
        }
        return $response;
      }

      function request_api(string $url, string $method, string $json_data = null)
      {
        $token_auth = "1|BDEfhjNfHzZ3bUkxBswkWJnuCETmUVgpE3ILbuRR";
        $curl = curl_init();

        curl_setopt_array($curl, [
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_CUSTOMREQUEST => $method,
          CURLOPT_POSTFIELDS => $json_data,
          CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json;',
            'Authorization: Bearer ' . $token_auth,
          ]
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          popup("cURL Error #:" . $err);
          exit("cURL Error #:" . $err);
        } else {
          return $response;
        }
      }

      function popup($data)
      {
        // echo "<pre style='position: absolute'>";
        // print_r($data);
        // echo "</pre>";
        echo "<script> alert('$data')</script>";
      }

      function pppd($data)
      {
        popup($data);
        exit($data);
      }
