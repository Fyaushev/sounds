#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "../../config.h"
#include "php_sounds.h"

#include <string>
#include <vector>
#include "../../sounds_cpp_api.cc"

static zend_function_entry sounds_functions[] = {
	PHP_FE(sounds_crop, NULL)
	PHP_FE(sounds_volume, NULL)
	PHP_FE(sounds_info, NULL)
	PHP_FE(sounds_classify, NULL)
	PHP_FE(sounds_test, NULL)
    {NULL, NULL, NULL}
};

zend_module_entry sounds_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
    STANDARD_MODULE_HEADER,
#endif
    PHP_SOUNDS_EXTNAME,
    sounds_functions, // functions
	NULL, // MINIT
	NULL, // MSHUTDOWN
	NULL, // RINIT
    NULL, // RSHUTDOWN
    NULL, // MINFO
#if ZEND_MODULE_API_NO >= 20010901
    PHP_SOUNDS_VERSION,
#endif
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_SOUNDS
ZEND_GET_MODULE(sounds)
#endif

PHP_FUNCTION(sounds_crop) {
	char *name;
	int name_len;
	char *new_name;
	int new_name_len;
	long left = 0;
	long right = -1;
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss|ll", &name, &name_len, &new_name, &new_name_len, &left, &right) == FAILURE) {
		RETURN_NULL();
	}
	
	php_printf("sounds_crop(%s, %s, %ld, %ld)\n", name, new_name, left, right);
	
	string name_str(name);
	string new_name_str(new_name);
	name_str = PATH_TO_AUDIOS_PHP + "/" + FILE_SAVE_DIRECTORY + "/" + name_str;
	new_name_str = PATH_TO_AUDIOS_PHP + "/" + FILE_SAVE_DIRECTORY + "/" + new_name_str;
	
	string ret = sounds_crop(name_str, new_name_str, (int)left, (int)right);
	php_printf("ret = %s\n", ret.c_str());
	
	RETURN_NULL();
}

PHP_FUNCTION(sounds_volume) {
	char *name;
	int name_len;
	char *new_name;
	int new_name_len;
	double k;
	long left = 0;
	long right = -1;
	long smooth = 0;
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ssd|lll", &name, &name_len, &new_name, &new_name_len, &k, &left, &right, &smooth) == FAILURE) {
		RETURN_NULL();
	}
	
	php_printf("sounds_volume(%s, %s, %lf, %ld, %ld, %d)\n", name, new_name, k, left, right, (int) smooth);
	
	string name_str(name);
	string new_name_str(new_name);
	name_str = PATH_TO_AUDIOS_PHP + "/" + FILE_SAVE_DIRECTORY + "/" + name_str;
	new_name_str = PATH_TO_AUDIOS_PHP + "/" + FILE_SAVE_DIRECTORY + "/" + new_name_str;
	
	string ret = sounds_volume(name_str, new_name_str, k, (int) left, (int) right, (bool) smooth);
	php_printf("ret = %s\n", ret.c_str());
	
	RETURN_NULL();	
}


PHP_FUNCTION(sounds_info) {
    char *name;
    int name_len;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &name, &name_len) == FAILURE) {
        RETURN_NULL();
    }
	
	string name_str(name);
	name_str = PATH_TO_AUDIOS_PHP + "/" + FILE_SAVE_DIRECTORY + "/" + name_str;
	
	array_init(return_value);
	
    vector<double> ret = sounds_info(name_str);
	//php_printf("%d, %d\n", (int) ret[0], (int) ret[1]);

    add_index_long(return_value, 0, (int) ret[0]);
	add_index_long(return_value, 1, (int) ret[1]);
	add_index_long(return_value, 2, (int) ret[2]);
	add_index_long(return_value, 3, (int) ret[3]);
	add_index_long(return_value, 4, (int) ret[4]);
	add_index_double(return_value, 5, ret[5]);
}

PHP_FUNCTION(sounds_classify) {
    char *name;
    int name_len;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &name, &name_len) == FAILURE) {
        RETURN_NULL();
    }
	
	string name_str(name);
	name_str = PATH_TO_AUDIOS_PHP + "/" + FILE_SAVE_DIRECTORY + "/" + name_str;
	
    string ret = sounds_classify(name_str);
	php_printf("ret = %s<br>", ret.c_str());

    RETURN_STRING(ret.c_str(), 1);
}


PHP_FUNCTION(sounds_test) {
	long x;
	
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &x) == FAILURE) {
        RETURN_NULL();
    }
	
    //char *my_str;
	//zval *mysubarray;
	array_init(return_value);
	add_index_long(return_value, 0, 123);
	add_next_index_string(return_value, "hey", 1);
	add_next_index_long(return_value, x+1000);
	
	
	//convert_to_string(x);
	//string ret(x);
	//ret += " yeehaw";

    //RETURN_STRING(ret.c_str(), 1);
}




