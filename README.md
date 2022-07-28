# pdf-laravel

# Step 1 :

sudo apt-get install imagemagick

sudo apt-get install pdftk

sudo apt-get install poppler-utils

# Step 2


comment the below line on /etc/ImageMagick-6/policy.xml 

`<policy domain="coder" rights="none" pattern="PS" />`

`<policy domain="coder" rights="none" pattern="PS2" />`

`<policy domain="coder" rights="none" pattern="PS3" />` 

`<policy domain="coder" rights="none" pattern="EPS" />` 

`<policy domain="coder" rights="none" pattern="PDF" />` 

`<policy domain="coder" rights="none" pattern="XPS" />`


# How to use ?

      $pdfObj = new PdfFiller();    
      $pdfObj->fill(['First Name' => 'John', 'License Period' => '2 Years',
    'signatures' => [
        ["pg_no" => 2, "image_loc" => 'S3_URL', "bottom" => "300", "left" => "150"], 
        ["pg_no"=>  1, "image_base_64"=> "data:image/jpeg;base64 ..." ,"bottom"=>"102","left"=>"25"]
        
    ]], "ok.pdf", "done.pdf"));
