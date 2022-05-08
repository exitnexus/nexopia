#if _MSC_VER >= 1400
#include <iostream>
#include "Parser.h"
#include "Scanner.h"
#include <sys/timeb.h>
#include <wchar.h>
#include "stdarg.h"

void parse(){
    BBCode::Scanner *scanner = new BBCode::Scanner(L"C:\\Documents and Settings\\Tom\\My Documents\\Visual Studio 2005\\Projects\\bbcode\\bbcode\\bbcode.txt");
    BBCode::Parser *parser = new BBCode::Parser(scanner);
    parser->Parse();
    if (parser->errors->count == 0) {
//cout << string(parser->str.begin(), parser->str.end()).c_str();
    }

    delete parser;
    delete scanner;
}


int main(int argc, char** argv){

    while(true){
        parse();
    }

}

#else
#include <iostream>
#include "Parser.h"
#include "Scanner.h"
#include <sys/timeb.h>
#include <wchar.h>
#include "stdarg.h"

#include "ruby.h"


static VALUE method_parse(VALUE, VALUE);
VALUE BBCodeParser = Qnil;

// The initialization method for this module
extern "C" void Init_bbcode() {
	BBCodeParser = rb_define_module("BBCode");
	rb_define_module_function(BBCodeParser, "parse", (VALUE(*)(ANYARGS))method_parse, 1);
}


static VALUE method_parse(VALUE self, VALUE rstr){
/*
    va_list ap;
    
    va_start(ap, n);
    VALUE self = va_arg(ap, VALUE); 
    VALUE rstr = va_arg(ap, VALUE); 
    va_end(ap);
*/
    const char *str = STR2CSTR(rstr);
    BBCode::Scanner *scanner = new BBCode::Scanner(str, strlen(str));
    BBCode::Parser *parser = new BBCode::Parser(scanner);
    parser->Parse();
//    if (parser->errors->count == 0) {
//        wcout << parser->str.c_str();
//    }
    if (parser->errors->count == 0) {
    }

    string utf8 = string(parser->str.begin(), parser->str.end());

    delete parser;
    delete scanner;

    return rb_str_new2(utf8.c_str());

}


#endif

