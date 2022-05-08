

#if !defined(COCO_PARSER_H__)
#define COCO_PARSER_H__

#include <string>
#include <vector>
#include <algorithm>
#include <map>

using namespace std;


#include "Scanner.h"

namespace BBCode {


class Errors {
public:
	int count;			// number of errors detected

	Errors();
	void SynErr(int line, int col, int n);
	void Error(int line, int col, const wchar_t *s);
	void Warning(int line, int col, const wchar_t *s);
	void Warning(const wchar_t *s);
	void Exception(const wchar_t *s);

}; // Errors

class Parser {
private:
	enum {
		_EOF=0,
		_whitespace=1,
		_word=2,
		_escapedslash=3,
		_escapedbracket=4,
	};
	int maxT;

	Token *dummyToken;
	int errDist;
	int minErrDist;

	void SynErr(int n);
	void Get();
	void Expect(int n);
	bool StartOf(int s);
	void ExpectWeak(int n, int follow);
	bool WeakSeparator(int n, int syFol, int repFol);

public:
	Scanner *scanner;
	Errors  *errors;

	Token *t;			// last recognized token
	Token *la;			// lookahead token

vector<wstring> tags;
	wstring str;
	wstring mode;
	bool first_list_element;
	wstring last_list;
	
	void SemErr(int n){
		fprintf(stderr, "Sem err %i \n", n);
	}
	
	wstring PushTag(wstring tag, wstring attrs=L""){
		wstring output = L"<" + tag + attrs + L">";
		this->tags.push_back(tag);
		return output;
	}
	
	wstring PopTag(wstring tag){
		wstring output = L"";
		if (find(this->tags.begin(), this->tags.end(), tag) != this->tags.end()){
	    	vector<wstring> temp_stack;
	        //pop the tags off
	        wstring elt;
			while((elt = this->tags.back()) != tag){
				this->tags.pop_back();
				output += L"</" + elt + L">";
				temp_stack.push_back(elt);
			}
			this->tags.pop_back();
			output += L"</" + tag + L">";
			
			//re-open
			for(vector<wstring>::iterator it = temp_stack.begin(); it != temp_stack.end(); it++){
				wstring elt = (*it);
				output += L"<" + elt + L">";
				this->tags.push_back(elt);
			}
		}else{
			return wstring(this->t->val); //Warning --- this only works because we parse only 1 token.
								// If we wanted better, try storing the buffer begin and end
								// pos and use the original buffer text.
		}
		return output;
	}
	
	bool valid_url(wstring url){
		return ((url.substr(0,7) == L"http://") || (url.substr(0,8) == L"https://") || (url.substr(0,1) == L"/"));
	}
	
	template<class T>
	basic_string<T> gsub(basic_string<T> source, const basic_string<T>& find, const basic_string<T>& replace ){
		size_t j;
		if (find.length() == 0)
			return source; 
		for (;(j = source.find( find )) != basic_string<T>::npos;){
			source.replace( j, find.length(), replace );
		}
		return source;
	}
	
	wstring forumcode_safeurl(wstring url){
		map<wstring,wstring> replace;
		replace[wstring(L"%")]  = L"%25";
		replace[wstring(L"\"")] = L"%22";
		replace[wstring(L"'")]  = L"%27";
		replace[wstring(L"<")]  = L"%3C";
		replace[wstring(L">")]  = L"%3E";
		replace[wstring(L"#")]  = L"%23";

		for(map<wstring, wstring>::iterator it = replace.begin(); it != replace.end(); it++){
			wstring find = (*it).first;
			wstring replacem = (*it).second;
			url = gsub(url, find, replacem);
		}

		for(int i=1; i <= 31; i++){
			wchar_t buf[1];
			swprintf(buf, 1, L"%c", i);
			url = gsub(url, wstring(buf), wstring(L""));
		}
	
		for(int i=127; i <= 255; i++){
			wchar_t buf[1];
			swprintf(buf, 1, L"%c", i);
			url = gsub(url, wstring(buf), wstring(L""));
		}
		//do{
			//$url1 = $url;
			//$url = preg_replace("/(?i)j(\s*)a(\s*)v(\s*)a(\s*)s(\s*)c(\s*)r(\s*)i(\s*)p(\s*)t(\s*):/","", $url); //ie javascript: with spaces between it
			//$url = preg_replace("/(?i)v(\s*)b(\s*)s(\s*)c(\s*)r(\s*)i(\s*)p(\s*)t(\s*):/","", $url); //ie vbscript: with spaces between it
			//$url = preg_replace("/(?i)d(\s*)a(\s*)t(\s*)a(\s*):/","", $url); //ie data: with spaces between it, used with: data:text/html;base64,.....
		//}while($url1 != $url);

		return url;
	}
	
	wstring urlencode(wstring text){
		return text;
	}

	wstring forumcode_url(wstring url){
		url = gsub(url, wstring(L"\""), wstring(L"%22"));
		return PushTag(L"a", wstring(L" class=\"body\" href=\"") + url + L"\" target=\"_new\"");
	}
	
	wstring forumcode_user(wstring url){
		return PushTag(L"a", wstring(L" class=body target=_new href=\"/users/") + urlencode(forumcode_safeurl(url)) + L"\"");
	}
	
	wstring forumcode_email(wstring url){
		return PushTag(L"a", wstring(L"class=body href=\"/mailto:") + forumcode_safeurl(url) + L"\"");
	}

	wstring forumcode_image(wstring url){
		wstring tag;
		if (valid_url(url))
			tag = wstring(L"<img src=\"") + gsub(url, wstring(L"\""), wstring(L"%22")) + wstring(L"\" border=\"0\"/>"); //.gsub('"',"%22")
		else if (wstring(this->t->val) == L"[/img]")
			tag = L"[img]" + url + L"[/img]";
		else
			tag = L"[img]" + url;
		return tag;
	}
	


	Parser(Scanner *scanner);
	~Parser();
	void SemErr(const wchar_t* msg);

	void BBCode();
	void BBTag(wstring &tag);
	void URLTag(wstring &output);
	void ListTag(wstring &output);
	void IMGtag(wstring &tag);
	void SimpleTag(wstring &output);
	void DecorationTag(wstring &tag);
	void QuoteTag(wstring &tag);
	void ATTR(wstring &tag);

	void Parse();

}; // end Parser

}; // namespace


#endif // !defined(COCO_PARSER_H__)

