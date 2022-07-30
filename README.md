# pdf-laravel
This packages helps you fill pdf as well as add signature ,using free linux tools. 

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

# Step - 4

    php artisan vendor:publish
 
 This will create a config file[pdffiller.pdf] in config folder.
 
 Keep all the pdf to be filled inside the config folder that you have specified. Cause the program will search in that folder for the pdf file that you will supply in the function.
 
 The program bascially creates a sub folder [pdata] and keep all the modified pdf in that folder.
 
 

# How to use ?

      $pdfObj = new PdfFiller();    
      $pdfObj->fill(['First Name' => 'John', 'License Period' => '2 Years',
    'signatures' => [
        ["pg_no" => 2, "image_loc" => 'S3_URL', "bottom" => "300", "left" => "150"], 
        ["pg_no"=>  1, "image_base_64"=> "data:image/jpeg;base64 ..." ,"bottom"=>"102","left"=>"25"]
        
    ]], "doctor_melvin.pdf", "doctor_melvin_filled.pdf"));
    
    
  
