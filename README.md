digium-stats-php
================

Pull stats from Digium Voip Switchvox with the Extend Api php library.

Steps to get library to work on Ubuntu 12
=========================================

  sudo apt-get install php5 php5-dev pear php5-curl 
  sudo apt-get install libcurl4-dev-openssl
  sudo pear install XML_Serializer-0.19.2
  sudo pecl install pecl_http-1.7.6  
  
  add "extension=http.so" to php.ini
  
pecl_http is 2.0 and will not work with the library as written you need the 1.7.6 version
