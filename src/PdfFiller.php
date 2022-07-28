<?php

namespace Andrew\PdfFillerLaravel;

use Exception;
use Illuminate\Support\Facades\Storage;
use mikehaertl\pdftk\Pdf;

class PdfFiller
{
    private $dataToFill = [];
    private $pdfFolders = "";
    private $pdfTmpFolders = "";
    private $pdfMainFolders = "";
    private $pdfFile = "";
    private $outputFileName = "";
    private $paperType = "a4"; // a4 size or letter
    private $s3PathListHolder = [];
    private $cacheHolder = [];

    function __construct()
    {
        $this->pdfFolders = config('pdffiller.base_pdf_holder_path');
        if ($this->pdfFolders && !file_exists(base_path($this->pdfFolders))) {
            mkdir(base_path($this->pdfFolders), 0777, true);
        }
    }

    private function init($dataToFill, $pdfFile, $outputFileName)
    {
        $tmp_rnd_folder_name = $this->generateRandomString(5);
        $this->pdfTmpFolders = base_path("$this->pdfFolders/tmp/$tmp_rnd_folder_name");
        $this->pdfMainFolders = base_path("$this->pdfFolders/pdata");
        if (str_contains(PHP_OS, 'Linux')) {
            if (!file_exists($this->pdfMainFolders)) {
                mkdir($this->pdfMainFolders, 0777, true);
            }
            if (!file_exists($this->pdfTmpFolders)) {
                mkdir($this->pdfTmpFolders, 0777, true);
            }
        }
        $this->pdfFile = base_path($this->pdfFolders . DIRECTORY_SEPARATOR . $pdfFile);
        if (!file_exists($this->pdfFile)) {
            throw new Exception("Pdf file not found.");
        }
        $this->outputFileName = $outputFileName;
        $this->dataToFill = $dataToFill;
    }

   /**
     * Get the filed list name specified in the pdf file.
     * @param $pdf_file_name string|null FileName of the pdf file | will search in the folder provided in config/pdffiller.php | if you cam't find one publish it.
     * @return bool|\mikehaertl\pdftk\DataFields
     * @throws Exception
     */
    public function getAllFieldDetails($pdf_file_name)
    {
        if($pdf_file_name) {
            $pdf = new Pdf($pdf_file_name);
            $data = $pdf->getDataFields();
            if ($data === false) {
                throw new Exception($pdf->getError());
            }
            return $data;
        }else{
            throw new Exception("Pdf file not found.");
        }
    }

    /**
     * Storing s3 dns cache
     * @param $s3Path
     * @return bool|mixed|string
     */
    private function s3DNSCache($s3Path)
    {
        $s3Path = trim($s3Path);
        $key = md5($s3Path);
        if (!in_array($key, $this->s3PathListHolder)) {
            $result = Storage::disk('s3')->temporaryUrl($s3Path, now()->addMinutes(5));
            $this->s3PathListHolder[$key] = $result;
            return $result;
        } else {
            return $this->s3PathListHolder[$key];
        }
    }

    /**
     * Read images from cache if exist.
     * @param $file_url
     * @return false|mixed|string
     */
    private function readFromCache($file_url)
    {
        $file_url = trim($file_url);
        $key = md5($file_url);
        if (in_array($key, $this->cacheHolder)) {
            return $this->cacheHolder[$key];
        } else {
            $raw_data = file_get_contents($file_url);
            $this->cacheHolder[$key] = $raw_data;
            return $raw_data;
        }
    }

    /** Convert base64 to image and save it to cache for later use.
     * @param $base64_string
     * @param $output_file
     * @return mixed
     */
    private function convertBase64ToImage($base64_string, $output_file)
    {
        $ifp = fopen($output_file, "wb");
        $data = explode(',', $base64_string);
        fwrite($ifp, base64_decode($data[1]));
        fclose($ifp);
        return $output_file;
    }

    /**
     * Generating random string
     * @param $length
     * @return string
     */
    private function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * @return string Return the final pdf file location filled with all data in it.
     * Works only if signatures list is specified.
     * It can handle both the aws provided filename Eg: craft/signature/craft_1627880159.jpeg
     * and also the base64 image type.
     * the input for this function is given below
     * [
     * ".." => "Johnson",
     * "signatures"=>
     * ["pg_no"=>"1","image_loc"=>"","image_base_64"=>"","bottom"=>"102","left"=>"25"],
     * ["pg_no"=>"1","image_loc"=>"","image_base_64"=>"","bottom"=>"102","left"=>"25"]]
     * ]
     * @throws \Exception
     */
    private function signHandlerlogic()
    {
        foreach ($this->dataToFill as $key => $value) {
            if ($key == "signatures" && is_array($this->dataToFill["signatures"])) {
                $cntSign = 1;
                $totalSignature = count($this->dataToFill["signatures"]);
                $cmdHandler = "magick";
                if (!str_contains(PHP_OS, "WIN")) {
                    $cmdHandler = "convert";
                }
                foreach ($this->dataToFill["signatures"] as $sign) {
                    $bottom = "0";
                    $left = "0";
                    $image_loc = "";
                    $image_base_64 = "";
                    $pg_no = 1;
                    extract($sign);
                    $raw_data = "";
                    if (isset($sign["image_loc"]) && $image_loc != "") {
                        $extension = 'jpg';
                        if (filter_var($image_loc, FILTER_VALIDATE_URL) == false) {
                            $file_url = $this->s3DNSCache($image_loc);
                        } else {
                            $file_url = $image_loc;
                            $extension = pathinfo($file_url, PATHINFO_EXTENSION);
                        }
                        $raw_data = $this->readFromCache($file_url);
                        if ($raw_data) {
                            $filename = $this->pdfTmpFolders . DIRECTORY_SEPARATOR . "sign_$cntSign.$extension";
                            $fobj = fopen($filename, "w");
                            fwrite($fobj, $raw_data);
                            fclose($fobj);
                            if ($extension == "png") {
                                $new_filename = str_replace('.png', '.jpg', $filename);
                                exec("convert $filename -background white -flatten $new_filename && rm $filename");
                            }
                        }
                    } elseif (isset($sign["image_base_64"]) && $image_base_64 != "") {
                        $raw_data = $image_base_64;
                        if ($raw_data) {
                            $this->convertBase64ToImage($raw_data, $this->pdfTmpFolders . DIRECTORY_SEPARATOR . "sign_$cntSign.jpg");
                        }
                    }
                    if (!$raw_data) {
                        continue;
                    }

                    exec("$cmdHandler $this->pdfTmpFolders" . DIRECTORY_SEPARATOR . "sign_$cntSign.jpg -resize 80% $this->pdfTmpFolders" . DIRECTORY_SEPARATOR . "sign_$cntSign.jpg && $cmdHandler $this->pdfTmpFolders" . DIRECTORY_SEPARATOR . "sign_$cntSign.jpg -resize 26% -transparent white -page $this->paperType+$left+$bottom -quality 75 $this->pdfTmpFolders" . DIRECTORY_SEPARATOR . "sign_$cntSign.pdf");
                    $cntSign++;
                }
                $mains = $this->pdfTmpFolders . DIRECTORY_SEPARATOR . $this->outputFileName;
                exec("pdftk $mains dump_data | grep NumberOfPages", $output);
                $nos_of_pg = 0;
                if (count($output)) {
                    $nos_of_pg = trim(str_replace("NumberOfPages:", "", $output[0]));
                }
                foreach (range(1, $nos_of_pg) as $pg) {
                    $split_file_name = $this->pdfTmpFolders . DIRECTORY_SEPARATOR . "jtmp_$pg.pdf";
                    exec("pdftk $mains cat $pg output $split_file_name");
                }
                $sign_cnt = 1;
                foreach ($this->dataToFill["signatures"] as $signObj) {
                    $sign = $this->pdfTmpFolders . DIRECTORY_SEPARATOR . "sign_" . $sign_cnt . ".pdf";
                    $sign_img = $this->pdfTmpFolders . DIRECTORY_SEPARATOR . "sign_" . $sign_cnt . ".pdf";
                    if (file_exists($sign)) {
                        $pg_no = 1;
                        if (isset($signObj['pg_no'])) {
                            $pg_no = $signObj['pg_no'];
                        }
                        $stamp_pdf = $this->pdfTmpFolders . DIRECTORY_SEPARATOR . "jtmp_$pg_no.pdf";
                        $new_stamp_pdf = $this->pdfTmpFolders . DIRECTORY_SEPARATOR . "s_jtmp_$pg_no.pdf";
                        exec("pdftk " . $stamp_pdf . " stamp $sign output " . $new_stamp_pdf);
                        exec("rm -rf $stamp_pdf && cp $new_stamp_pdf $stamp_pdf && rm -rf $new_stamp_pdf && rm -rf $sign");
                    }
                    $sign_cnt++;
                }
                // Rejoining
                $sappender = "pdftk ";
                foreach (range(1, $nos_of_pg) as $pg) {
                    $split_file_name = $this->pdfTmpFolders . DIRECTORY_SEPARATOR . "jtmp_$pg.pdf";
                    $sappender .= " $split_file_name";
                }
                $random_filename = $this->pdfTmpFolders . DIRECTORY_SEPARATOR . $this->generateRandomString(5) . ".pdf";
                $sappender .= " cat output $random_filename";
                exec($sappender);
                $mains = $random_filename;
                // Cleanup
                exec("cp $mains " . $mains . ".tmp && rm -f $this->pdfTmpFolders" . DIRECTORY_SEPARATOR . "*.pdf && rm -f $this->pdfTmpFolders" . DIRECTORY_SEPARATOR . "*.jpg && rm -f $this->pdfTmpFolders" . DIRECTORY_SEPARATOR . "*.png && cp $mains.tmp $mains && rm -f " . $mains . ".tmp");
                // Re-Flatting
                $smain = $this->pdfMainFolders . DIRECTORY_SEPARATOR . $this->outputFileName;
                $only_name = basename($this->outputFileName, ".pdf");
                $tmp_file = $only_name . "_tmp.pdf";
                exec("cd $this->pdfTmpFolders && rm -f *.png && rm -f *.jpg && pdftoppm -png $mains pp && $cmdHandler *.png $tmp_file && rm -f $mains && cp $tmp_file $smain && rm -f $this->pdfTmpFolders" . DIRECTORY_SEPARATOR . $tmp_file . " && rm -rf $this->pdfTmpFolders");
                if (str_contains($smain, 'public')) {
                    $tmp = explode('public', $smain);
                    if (count($tmp)) {
                        $smain = end($tmp);
                    }
                }
                return $smain;
            }
        }
        throw new \Exception("No 'signatures' key found in the passed array.");
    }

    /**
     * This function takes in data that need to be filled in the pdf and returns the pdf with filled data which is non-editable.
     * You can also pass signature data to this function to fill the pdf with signature.
     * Eg: $dataToFill = [
     * 'First Name' => 'John', 'License Period' => '2 Years',
     * 'signatures' => [
     * ['image_loc' => 'signature/doctor_johnson.jpg', "bottom" => 350, "left" => 200],
     * ['image_base_64' => 'data:image/jpeg;base64,/9j/4AAQ....', "bottom" => 50, "left" => 200]
     * ];
     * Note: 'First Name' <- The key should be the field name in the pdf.
     * $pdfFile = 'form_template.pdf';  The folder location can be specified in the config file. php artisan vendor:publish
     * $outputFileName = 'form_filled.pdf'; The output file name will also be present in the specified_folder/pdata/form_filled.pdf.
     * @param $dataToFill array data to pre fill
     * @param $pdfFile string only the file name of the pdf from the default location
     * @param $outputFileName string any_name.pdf
     * @return string
     * @throws Exception
     */
    public function fill($dataToFill, $pdfFile, $outputFileName)
    {
        $this->init($dataToFill, $pdfFile, $outputFileName);
        if (count($this->dataToFill)) {
            $pdf = new Pdf($this->pdfFile);
            $outputFullFileLoc = $this->pdfTmpFolders . DIRECTORY_SEPARATOR . $this->outputFileName;
            $tmp_OutputFullFileLoc = $this->pdfTmpFolders . DIRECTORY_SEPARATOR . 'tmp_' . $this->outputFileName;
            $newDataToFill = array_filter($this->dataToFill, function ($item) {
                if ($item != "signatures") {
                    return true;
                }
                return false;
            }, ARRAY_FILTER_USE_KEY);
            $result = $pdf->fillForm($newDataToFill)->needAppearances()
                ->saveAs($outputFullFileLoc);
            exec("pdftk $outputFullFileLoc output $tmp_OutputFullFileLoc flatten");
            exec("cp $tmp_OutputFullFileLoc $outputFullFileLoc && rm -f $tmp_OutputFullFileLoc");
            if ($result === false) {
                $error = $pdf->getError();
                throw new \Exception($error);
            }
            if (isset($this->dataToFill["signatures"])) {
                return $this->signHandlerlogic();
            } else {
                if (file_exists($outputFullFileLoc)) {
                    $new_file_loc = $this->pdfMainFolders . DIRECTORY_SEPARATOR . $this->outputFileName;
                    if (file_exists($new_file_loc)) {
                        unlink($new_file_loc);
                    }
                    copy($outputFullFileLoc, $new_file_loc);
                    unlink($outputFullFileLoc);
                    rmdir($this->pdfTmpFolders);
                }
                return basename($new_file_loc);
            }
        } else {
            throw new \Exception("No data supplied to fill into the pdf.");
        }
    }
}
