# hcls-shex

A PHP script to generate a [ShEX](http://shex.io/) document for the [HCLS Dataset Description Guidelines](https://www.w3.org/TR/hcls-dataset/). 

To run:

````  
php generateShex4HCLS.php
````

and this will generate [hcls.shex](https://github.com/micheldumontier/hcls-shex/blob/master/hcls.shex) as output. You can use the shex content to validate [HCLS dataset description files](https://www.w3.org/TR/hcls-dataset/)) such as [chembl-full-description.ttl](https://github.com/micheldumontier/hcls-shex/blob/master/chembl-full-description.ttl) using [http://rdfshape.weso.es](http://rdfshape.weso.es). 

The SHeX file contains 3 shapes: HCLSSummaryShape, HCLSVersionShape, and HCSLDistributionShape. Three more files are also generated where we force SHOULD and/or MAY to be mandatory - which can be useful for generating error messages in each or both of those levels (in addition to the base requirements). Make note that these have different shape names (e.g. HCLSSummaryForceShouldShape). If you're using a SHeX validator like RDFShape, make sure to use the appropriate shapename for the shapemap (e.g. :chembl @`<HCLSSummaryShape>`)
 
