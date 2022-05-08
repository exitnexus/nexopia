/***
 * 
 *  Created by Sean Healy
 *  sean@pointscape.org
 * 
 *  RAP_clib.c
 *  -------
 *  A mini version of RAP that simplifies a lot of things for testing reasons.
 * 
 ***/

#include <stdio.h>
#include <main/php.h>
#include <main/SAPI.h>
#include <sapi/embed/php_embed.h>
#include <main/php_main.h>
#include <main/php_variables.h>
#include <main/php_ini.h>
#include <zend_ini.h>
#include "ruby.h"
#include "string.h"
#include <signal.h>
#ifdef ZTS
void ***tsrm_ls;
#endif

#ifdef ZTS
#define PTSRMLS_D        void ****ptsrm_ls
#define PTSRMLS_DC       , PTSRMLS_D
#define PTSRMLS_C        &tsrm_ls
#define PTSRMLS_CC       , PTSRMLS_C

#define PHP_EMBED_START_BLOCK(x,y) { \
    void ***tsrm_ls; \
    php_embed_init(x, y PTSRMLS_CC); \
    zend_first_try {

#else
#define PTSRMLS_D
#define PTSRMLS_DC
#define PTSRMLS_C
#define PTSRMLS_CC
#endif

typedef struct rap_globals {
	VALUE result;
	VALUE headers;
	VALUE rap_log;
	VALUE request;
	VALUE callback_object;
	zval *ruby_vars;
} rap_globals;

#ifdef ZTS
int rap_globals_id;
#else
rap_globals globals;
#endif

#ifdef ZTS
# define   RAP_G(v)     \
             (((rap_globals*)(*((void ***)tsrm_ls))[(rap_globals_id)-1])->v)
#else
# define   RAP_G(v)     (globals.v)
#endif

// We can be transferring arrays of arrays (and so forth), so
// we need a stack to keep track of our position in each
// array.
struct iter_stack {
	struct iter_stack *prev;
	int count;
};

static struct iter_stack *foreach_iter_count = NULL;

#define FOREACH(hash, iter, val) \
	if ((hash) != Qnil) { \
		struct iter_stack *tmp = foreach_iter_count; \
		foreach_iter_count = emalloc(sizeof(struct iter_stack)); \
		foreach_iter_count->prev = tmp; \
		foreach_iter_count->count = 0; \
		rb_iterate(rb_each, (hash), (iter), (val)); \
		tmp = foreach_iter_count; \
		foreach_iter_count = foreach_iter_count->prev; \
		efree(tmp); \
	}
		
/****************************** header stuff  ******************************/

VALUE method_php_exec(VALUE self, VALUE code, VALUE pre, VALUE vars, VALUE r,
		VALUE h, VALUE l);
/*VALUE method_test_RAP_clib(VALUE self);
 VALUE method_php_begin(VALUE self);
 VALUE method_php_end(VALUE self);*/
static void rap_register_variables(zval *track_vars_array TSRMLS_DC);
static void set_val(VALUE in, zval *val);
/******************************* SAPI stuff *******************************/

static int rap_startup(sapi_module_struct *sapi_module) {
	if (php_module_startup(sapi_module, NULL, 0) == FAILURE) {
		//rb_raise(rb_eRuntimeError, "Failure in rap startup code.\n");
		return FAILURE;
	}

	return SUCCESS;
}

static void rap_flush(void *server_context) {
	if (fflush(stdout)==EOF) {
		//rb_raise(rb_eRuntimeError, "Aborted connection.\n");
		php_handle_aborted_connection();
	}
}

static int rap_deactivate(TSRMLS_D) {
	fflush(stdout);
	//	php_handle_aborted_connection();
	return SUCCESS;
}

static void rap_register_variables(zval *track_vars_array TSRMLS_DC);
static void php_embed_send_header(sapi_header_struct *sapi_header,
void *server_context TSRMLS_DC)
{
}

/**
 * A function that the intereperater points to to report SAPI errors
 **/
static void rap_sapi_error(int type, const char *fmt, ...) {
	va_list ap;
	va_start(ap, fmt);
	char str[256];
	vsnprintf(str, 256, fmt, ap);
	va_end(ap);

	//rb_raise(rb_eRuntimeError, str);
	fprintf(stderr, "%s\n", str);
}

/* Triggered at the beginning of a thread */
static void rap_globals_ctor(rap_globals *globals TSRMLS_DC)
{
	globals->result = Qnil;
	globals->headers = Qnil;
	globals->rap_log = Qnil;
}

/* Triggered at the end of a thread */
static void rap_globals_dtor(rap_globals *globals TSRMLS_DC)
{
}

static sapi_module_struct rap_sapi_module = { "rap sapi", /* name */
"RAP SAPI", /* pretty name */

rap_startup, /* startup */
php_module_shutdown_wrapper, /* shutdown */

NULL, /* activate */
rap_deactivate, /* deactivate */

NULL, /* unbuffered write */
rap_flush, /* flush */
NULL, /* get uid */
NULL, /* getenv */

rap_sapi_error, /* error handler */

NULL, /* header handler */
NULL, /* send headers handler */
php_embed_send_header, /* send header handler */

NULL, /* read POST data */
NULL, /* read Cookies */

rap_register_variables, /* register server variables */
NULL, /* Log message */
NULL, /* Get request time */

STANDARD_SAPI_MODULE_PROPERTIES };


int rap_init(int argc, char **argv PTSRMLS_DC) {
	rap_sapi_module.sapi_error = rap_sapi_error;

#ifdef ZTS			/** zend thread safety **/
	zend_compiler_globals *compiler_globals;
	zend_executor_globals *executor_globals;
	php_core_globals *core_globals;
	sapi_globals_struct *sapi_globals;
	void ***tsrm_ls;

	tsrm_startup(1, 1, 0, NULL);

	compiler_globals = ts_resource(compiler_globals_id);
	executor_globals = ts_resource(executor_globals_id);
	core_globals = ts_resource(core_globals_id);
	sapi_globals = ts_resource(sapi_globals_id);
	tsrm_ls = ts_resource(0);
	*ptsrm_ls = tsrm_ls;
#endif

#ifdef ZTS
	ts_allocate_id(&rap_globals_id, sizeof(rap_globals), rap_globals_ctor,
			rap_globals_dtor);
#else
	rap_globals_ctor(&globals TSRMLS_CC);
#endif

	signal(SIGPIPE, SIG_IGN);

	sapi_startup(&rap_sapi_module);

	rap_sapi_module.executable_location = (char *)__func__;

	if (rap_sapi_module.startup(&rap_sapi_module) == FAILURE) {
		//rb_raise(rb_eRuntimeError, "Failure in startup.\n");
		fprintf(stderr, "Failed to start up.");
	}

	/* Set some Embedded PHP defaults */
	SG(options) |= SAPI_OPTION_NO_CHDIR;
	CG(interactive) = 0;
	return SUCCESS;
}

/****************************** hybrid stuff  ******************************/

/**
 * Called by php when things go wrong
 **/
static void php_log_message(char *message) {
	RAP_G(rap_log) = rb_str_cat2(RAP_G(rap_log), message);
	RAP_G(rap_log) = rb_str_cat2(RAP_G(rap_log), "\n");
}

/**
 * Called by php when new info is avaliable puts stuff into result
 **/
static int php_ub_write(const char *str, unsigned int str_length TSRMLS_DC)
{
	RAP_G(result) = rb_str_cat2(RAP_G(result), str);
	return 0;
}

/**
 * Called by php when headers are written in PHP.
 **/
int php_header_handler(sapi_header_struct *sapi_header,
		sapi_headers_struct *sapi_headers TSRMLS_DC) {
	RAP_G(headers) = rb_str_cat2(RAP_G(headers), sapi_header->header);
	RAP_G(headers) = rb_str_cat2(RAP_G(headers), "\n");
	return 0;
}

void run_php_file(char *file) {
	zend_file_handle file_handle;
	file_handle.type = ZEND_HANDLE_FILENAME;
	file_handle.filename = file;
	file_handle.handle.fp = NULL;
	file_handle.opened_path = NULL;
	file_handle.free_filename = 0;
	SG(request_info).path_translated = file;

	if (php_execute_script(&file_handle TSRMLS_CC) == FAILURE)
		fprintf(stderr, "Unable to run %s\n", file);
	//zend_eval_string("include '%s'", file)

}

/*********************** Variable stuff *******************************/

zval *create_php_global_object(char *name, char *value)
{
	zval *array_ptr;
	ALLOC_ZVAL(array_ptr);
	object_init(array_ptr);
	INIT_PZVAL(array_ptr);
	zend_hash_add(&EG(symbol_table), name, strlen(name)+1, &array_ptr, sizeof(array_ptr), NULL);
	return array_ptr;
}
zval *create_php_global_hash(char *name)
{
	zval *array_ptr;
	ALLOC_ZVAL(array_ptr);
	array_init(array_ptr);
	INIT_PZVAL(array_ptr);
	zend_hash_add(&EG(symbol_table), name, strlen(name)+1, &array_ptr, sizeof(array_ptr), NULL);
	return array_ptr;
}

/* Iterator function for ruby each loops over arrays */
static int php_array_foreach_iter(VALUE value, zval *array)
{
	int key;
	zval *val;

	/* In Php, arrays are actually ordered maps.  If we are copying
	 * over Ruby arrays, we set the key to the whole number
	 * sequence.  Ruby arrays are copied over with ordering
	 * maintained, hooray.
	 */
	// Can probably actually remove the foreach_iter_count,
	// now we use add_next_index_zval
	key = foreach_iter_count->count++;
	MAKE_STD_ZVAL(val);
	set_val(value, val);
		
	add_next_index_zval(array, val);

	return 0;
}

/* Iterator function for ruby each loops over hash tables */
static int php_hash_foreach_iter(VALUE keyvalue, zval *array)
{
	VALUE key, value;
	char *key_data;
	zval *val;

	/* In Php, arrays are actually ordered maps.  If we are
	 * converting from Ruby hashes, we have |key, value| pairs
	 * (although Ruby hashes, unlike Php arrays, are not ordered).
	 * So, we copy over the key and value.
	 */
	key = rb_funcall(rb_ary_entry(keyvalue, 0), rb_intern("to_s"), 0);
	key_data = estrndup(RSTRING(key)->ptr, RSTRING(key)->len);

	value = rb_ary_entry(keyvalue, 1);
	MAKE_STD_ZVAL(val);
	set_val(value, val);

	zend_hash_add(Z_ARRVAL_P(array), key_data, RSTRING(key)->len + 1,
	 	&val, sizeof(val), NULL);

	return 0;
}

/* Iterator function for ruby each loops */
static int php_var_foreach_iter(VALUE key, VALUE value, VALUE self)
{
	zval *val;
	MAKE_STD_ZVAL(val);
	VALUE str = rb_funcall(key, rb_intern("to_s"), 0);//rb_str_to_str(in);
	set_val(value, val);
	ZEND_SET_SYMBOL(&EG(symbol_table), StringValuePtr(str), val);
	return 0;
}

void make_php_array(VALUE in, zval *array)
{
	array_init(array);
	if (rb_class_of(in) == rb_cHash) {
		FOREACH(in, php_hash_foreach_iter, array);
	} else {
		FOREACH(in, php_array_foreach_iter, array);
	}
}

// Set PHP variable from Ruby variable.
static void set_val(VALUE in, zval *val)
{
	if (in == Qnil){
		ZVAL_NULL(val);
	}else if (in == Qfalse){
		ZVAL_FALSE(val);
	}else if (in == Qtrue){
		ZVAL_TRUE(val);
	}else if (rb_class_of(in) == rb_cFloat){
		ZVAL_DOUBLE(val, NUM2DBL(in));
	}else if (rb_class_of(in) == rb_cInteger){
		ZVAL_LONG(val, NUM2INT(in));
	}else if (rb_class_of(in) == rb_cFixnum){
		ZVAL_LONG(val, FIX2INT(in));
	}else if (rb_class_of(in) == rb_cBignum){
		// Will it fit in a regular Php int?  Let's find out
		// Php main/main.c defines PHP_INT_MAX as LONG_MAX
		if (rb_funcall(in, rb_intern(">"), 1, INT2NUM(LONG_MAX)) ==
		 	Qfalse) {
			// Will fit, send it as a regular int
			ZVAL_LONG(val, NUM2INT(in));
		} else {
			// Won't fit, send it as a string
			VALUE str = rb_funcall(in, rb_intern("to_s"), 0);
			char *akey = RSTRING(str)->ptr;
			int length = RSTRING(str)->len;
			ZVAL_STRINGL(val, akey, length, 1);
		}
	}else if (rb_class_of(in) == rb_cHash){
		make_php_array(in, val);
	}else if (rb_class_of(in) == rb_cArray){
		make_php_array(in, val);
	}else if (rb_class_of(in) == rb_cString){
		char *akey = RSTRING(in)->ptr;
		int length = RSTRING(in)->len;
		ZVAL_STRINGL(val, akey, length, 1);
	}else if (rb_funcall(in, rb_intern("class"), 0) ==
		  rb_const_get(rb_cObject, rb_intern("String"))){
		// Not a real Ruby string, but something we
		// previously treated as a real Ruby string.
		VALUE str = rb_funcall(in, rb_intern("to_s"), 0);
		char *akey = RSTRING(str)->ptr;
		int length = RSTRING(str)->len;
		ZVAL_STRINGL(val, akey, length, 1);
	}else if (rb_type(in) == T_OBJECT){
		// We have to generate a proxy object
		VALUE proxy = rb_funcall(RAP_G(callback_object),
		 	rb_intern("register_proxy_obj"), 1, in);
		// and set a string with a magic value, so RAP_RubyObject.php
		// can unpack it
		char *magic_val;
		spprintf(&magic_val, 0, "#!RAP:%s", RSTRING(proxy)->ptr);
		ZVAL_STRINGL(val, magic_val, strlen(magic_val), 1);
		efree(magic_val);
	}else{
		VALUE str = rb_funcall(in, rb_intern("to_s"), 0);
		char *akey = RSTRING(str)->ptr;
		int length = RSTRING(str)->len;
		ZVAL_STRINGL(val, akey, length, 1);
	}		
}


static void rap_register_variables(zval *track_vars_array TSRMLS_DC)
{

	char *script_name = SG(request_info).request_uri;
	int php_self_len = strlen(script_name);
	char *php_self = emalloc(php_self_len + 2);
	php_self[0] = '/';
	if (script_name) {
		memcpy(php_self + 1, script_name, php_self_len + 1);
	}

	/* In CGI mode, we consider the environment to be a part of the server
	 * variables
	 */
	php_import_environment_variables(track_vars_array TSRMLS_CC);
	/* Build the special-case PHP_SELF variable for the CGI version */

	FOREACH(rb_iv_get(RAP_G(request), "@server"), php_hash_foreach_iter, track_vars_array);

	efree(php_self);
}

/********************************** Exec stuff ****************************/
/**
 * Run the PHP code passed in.
 **/
VALUE php_exec(VALUE self, VALUE file, VALUE pre, VALUE p_result,
		VALUE p_headers, VALUE p_rap_log, VALUE request) {

	RAP_G(result) = p_result;
	RAP_G(headers) = p_headers;
	RAP_G(rap_log) = p_rap_log;
	RAP_G(request) = request;

	zend_first_try {
		SG(headers_sent) = 1;
		SG(request_info).no_headers = 1;
		SG(request_info).argc=0;
		SG(request_info).argv=NULL;
		SG(request_info).request_uri=StringValuePtr(file);

		if (php_request_startup(TSRMLS_C)==FAILURE) {
			php_module_shutdown(TSRMLS_C);
			return Qnil;
		}
		
		FOREACH(rb_iv_get(RAP_G(request), "@globals"), php_var_foreach_iter, Qnil);
		
		RAP_G(ruby_vars) = create_php_global_hash("_RUBY");
		FOREACH(rb_iv_get(RAP_G(request), "@ruby"), php_hash_foreach_iter, RAP_G(ruby_vars));
		FOREACH(rb_iv_get(RAP_G(request), "@get"), php_hash_foreach_iter, PG(http_globals)[TRACK_VARS_GET]);
		FOREACH(rb_iv_get(RAP_G(request), "@post"), php_hash_foreach_iter, PG(http_globals)[TRACK_VARS_POST]);
		FOREACH(rb_iv_get(RAP_G(request), "@files"), php_hash_foreach_iter, PG(http_globals)[TRACK_VARS_FILES]);
		FOREACH(rb_iv_get(RAP_G(request), "@cookies"), php_hash_foreach_iter, PG(http_globals)[TRACK_VARS_COOKIE]);
		
		zend_eval_string(StringValuePtr(pre), NULL, "Pre" TSRMLS_CC);
		
		run_php_file(StringValuePtr(file));
	}
	zend_catch {
		php_request_shutdown((void *) 0);
		return Qfalse;
	}
	zend_end_try();

	php_request_shutdown((void *) 0);

	return Qtrue;
}

/****************************** extention stuff  ******************************/

VALUE get_ruby_version_debug_option(zval **php_var, int debug);

// NOTE: Tom's variable stuff lives around around line 300.

// TODO: This needs to be made thread safe.
// TODO: Make this so it doesn't explode. (Finish the cleanup.)

// NOTE: azval->value.ht

// Keeps track of the RAP ruby object so we can make callbacks back out to it.
VALUE php_register_object(VALUE self, VALUE ruby_object)
{
	RAP_G(callback_object) = ruby_object;
}

VALUE rubyize_array(zval *val)
{
	HashTable *hash = val->value.ht;
	HashPosition pos;
	
	char *key;
	uint keylen;
	ulong idx;
	int type;
	zval **ppzval;
	
	/*
	// Prescan to see if the array is associative or not
	int associative = 0;
	for(zend_hash_internal_pointer_reset_ex(hash, &pos); zend_hash_has_more_elements_ex(hash, &pos) == SUCCESS; zend_hash_move_forward_ex(hash, &pos))
	{
		type = zend_hash_get_current_key_ex(hash, &key, &keylen, &idx, 0, &pos);
	
		if(type == HASH_KEY_IS_STRING)
		{
			associative = 1;
			break;
		}
	}
	*/
	
	VALUE ruby_hash = rb_hash_new();
	
	for(zend_hash_internal_pointer_reset_ex(hash, &pos); zend_hash_has_more_elements_ex(hash, &pos) == SUCCESS; zend_hash_move_forward_ex(hash, &pos))
	{
		type = zend_hash_get_current_key_ex(hash, &key, &keylen, &idx, 0, &pos);
		if(zend_hash_get_current_data_ex(hash, (void **)&ppzval, &pos) == FAILURE)
		{
			// This should never happen as we know that the key exists
			printf("You have done the imposible!");
			continue;
		}
		
		if(type == HASH_KEY_IS_STRING)
		{
			// The key is associative.
			rb_hash_aset(ruby_hash, rb_str_new2(key), get_ruby_version_debug_option(ppzval, 0));
		}
		else
		{
			rb_hash_aset(ruby_hash, INT2NUM(idx), get_ruby_version_debug_option(ppzval, 0));
		}
	}
	
	return ruby_hash;
}

VALUE get_ruby_version_debug_option(zval **php_var, int debug)
{
	VALUE ruby_var;
	
	if (Z_TYPE(**php_var) == IS_BOOL)
	{
		if(debug)
		{
			php_printf("\tType: boolean\n");
			php_printf("\tValue: %s\n", Z_BVAL(**php_var) ? "true" : "false" );
		}
		ruby_var = Z_BVAL(**php_var) ? Qtrue : Qfalse;
	}
	else if (Z_TYPE(**php_var) == IS_NULL)
	{
		if(debug)
		{
			php_printf("\tType: null\n");
		}
		ruby_var = Qnil;
	}
	else if (Z_TYPE(**php_var) == IS_LONG)
	{
		if(debug)
		{
			php_printf("\tType: long\n");
			php_printf("\tValue: %ld\n", Z_LVAL_P(*php_var));
		}
		ruby_var = INT2NUM(Z_LVAL_P(*php_var));
	}
	else if (Z_TYPE(**php_var) == IS_DOUBLE)
	{
		if(debug)
		{
			php_printf("\tType: double\n");
			php_printf("\tValue: %f\n", Z_DVAL_PP(php_var));
		}
		ruby_var = rb_float_new(Z_DVAL_PP(php_var));
	}
	else if (Z_TYPE(**php_var) == IS_STRING)
	{
		if(debug)
		{
			php_printf("\tType: string\n");
			php_printf("\tValue: %s\n", Z_STRVAL_P(*php_var));
		}				
		ruby_var = rb_str_new2(Z_STRVAL_P(*php_var));
	}
	else if (Z_TYPE(**php_var) == IS_ARRAY)
	{
		if(debug)
		{
			php_printf("\tType: array\n");
		}
		
		//rubyize_array(*php_var);
		
		//char * tmp = "bob";
		//ruby_var = rb_str_new2(tmp);
		ruby_var = rubyize_array(*php_var);
	}
	else
	{
		php_printf("\tUNHANDLED TYPE\n");
		ruby_var = Qnil;
	}
	
	return ruby_var;
}

VALUE get_ruby_version(zval **php_var)
{
	return get_ruby_version_debug_option(php_var, 0);
}

PHP_FUNCTION(ruby_callback)
{
	int debug = 0;

	int i, argc = ZEND_NUM_ARGS();
	zval ***args;
	args = (zval ***) safe_emalloc(argc, sizeof(zval **), 0);
	
	if(ZEND_NUM_ARGS() == 0 || zend_get_parameters_array_ex(argc, args) == FAILURE)
	{
		efree(args);
		WRONG_PARAM_COUNT;
	}
	
	if(debug)
		php_printf("<pre style=\"border: solid 1px #000; padding: 5px; background-color: #ffc;\"><u>Tracing PHP</u>:\n\n");

	// TODO: Split some of this off into a seperate function.
	VALUE parameter_array;
	parameter_array = rb_ary_new();
	VALUE current_parameter_ruby;
	zval **current_parameter_php;
	VALUE klass;
	VALUE method;
	for (i=0; i<argc; i++)
	{
		current_parameter_php = args[i];
		
		if(i == 0)
		{
			if(debug)
				php_printf("Klass Instance: %s\n", Z_STRVAL_P(*current_parameter_php));
				
			if (Z_TYPE(**current_parameter_php) == IS_STRING)
				klass = rb_str_new2(Z_STRVAL_P(*current_parameter_php));
			else
				php_printf("Invalid Type for Ruby Class");
		}
		else if(i == 1)
		{
			if(debug)
				php_printf("Method: %s\n\n", Z_STRVAL_P(*current_parameter_php));
			
			if (Z_TYPE(**current_parameter_php) == IS_STRING)
				method = rb_str_new2(Z_STRVAL_P(*current_parameter_php));
			else
				php_printf("Invalid Type for Ruby Method");
		}
		else
		{
			if(debug)
			{
				php_printf("Parameter %i:\n", i-2);
				current_parameter_ruby = get_ruby_version_debug_option(current_parameter_php, debug);
			}
			else
			{
				current_parameter_ruby = get_ruby_version(current_parameter_php);
			}
			parameter_array = rb_ary_push(parameter_array, current_parameter_ruby);
		}
	}
	if(debug)
		php_printf("</pre>");
	// END FUTURE SPLIT
	
	VALUE ruby_result;
	char * ruby_callback_method = "call";
	
	ruby_result = rb_funcall(RAP_G(callback_object), rb_intern(ruby_callback_method), 3, klass, method, parameter_array);
	
	if(0)
	{
		char * ruby_print_function = "print";
		char * start_pre = "<pre style=\"border: solid 1px #000; padding: 5px; background-color: #ccf;\"><u>Ruby Result</u>: ";
		char * end_pre = "</pre>";
		rb_funcall(RAP_G(callback_object), rb_intern(ruby_print_function), 1, rb_str_new2(start_pre));
		rb_funcall(RAP_G(callback_object), rb_intern(ruby_print_function), 1, ruby_result);
		rb_funcall(RAP_G(callback_object), rb_intern(ruby_print_function), 1, rb_str_new2(end_pre));
	}
	
	set_val(ruby_result, return_value);
	
	//TODO: Try moving this up maybe so things can be freed sooner.
	efree(args);
	
	return;
}

static function_entry php_rap_functions[] = {
	PHP_FE(ruby_callback, NULL)
	{NULL, NULL, NULL}
};

zend_module_entry php_rap_callback_module_entry = {
	STANDARD_MODULE_HEADER,
	"rap_callback",     /* extention name   */
	php_rap_functions,  /* function entries */
	NULL,               /* module init      */
	NULL,               /* module shutdown  */
	NULL,               /* rinit            */
	NULL,               /* rshutdown        */
	NULL,               /* module info      */
	"0.1.7",            /* version          */
	STANDARD_MODULE_PROPERTIES
};

/****************************** php stuff  ******************************/

// bosworth
// set an ini entry (you could also do this with set_ini in a php file)
int php_set_ini_entry(char *entry, char *value, int stage) {
	return zend_alter_ini_entry(entry, strlen(entry)+1, value, strlen(value)+1,
			PHP_INI_USER, stage);
}

//bosworth
VALUE php_init() {

	char *argv[2] = { "", NULL };

	rap_sapi_module.log_message = php_log_message;
	rap_sapi_module.ub_write = php_ub_write;
	rap_sapi_module.header_handler = php_header_handler;

	if (rap_init(1, argv PTSRMLS_CC) != SUCCESS) {
		//rb_raise(rb_eRuntimeError, "Failure initialization!\n");
		return Qnil;
	}

	zend_startup_module(&php_rap_callback_module_entry);  /***** Load the callback extention here. *****/

	php_set_ini_entry("max_execution_time", "0", PHP_INI_STAGE_ACTIVATE);
	php_set_ini_entry("variables_order", "S", PHP_INI_STAGE_ACTIVATE);

	return Qtrue;
}

VALUE php_destroy(VALUE self) {
#ifndef ZTS
	rap_globals_dtor(&globals TSRMLS_CC);
#endif

	php_module_shutdown(TSRMLS_C);
	sapi_shutdown();

#ifdef ZTS
	tsrm_shutdown();
#endif
	return Qtrue;
}

/****************************** ruby stuff ******************************/

/**
 * Defining a space for information and references about the module to be 
 * stored internally
 **/
VALUE RAP_clib = Qnil;

/**
 * The initialization method for this module
 **/
void Init_RAP_clib() {
	RAP_clib = rb_define_module("RAP_clib");
	rb_define_method(RAP_clib, "php_begin", php_init, 0);
	rb_define_method(RAP_clib, "php_exec", php_exec, 6);
	rb_define_method(RAP_clib, "php_end", php_destroy, 0);
	rb_define_method(RAP_clib, "php_register_object", php_register_object, 1);
}

