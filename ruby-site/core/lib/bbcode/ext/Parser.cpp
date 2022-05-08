

#include <wchar.h>
#include "Parser.h"
#include "Scanner.h"


namespace BBCode {


void Parser::SynErr(int n) {
	if (errDist >= minErrDist) errors->SynErr(la->line, la->col, n);
	errDist = 0;
}

void Parser::SemErr(const wchar_t* msg) {
	if (errDist >= minErrDist) errors->Error(t->line, t->col, msg);
	errDist = 0;
}

void Parser::Get() {
	for (;;) {
		t = la;
		la = scanner->Scan();
		if (la->kind <= maxT) { ++errDist; break; }

		if (dummyToken != t) {
			dummyToken->kind = t->kind;
			dummyToken->pos = t->pos;
			dummyToken->col = t->col;
			dummyToken->line = t->line;
			dummyToken->next = NULL;
			coco_string_delete(dummyToken->val);
			dummyToken->val = coco_string_create(t->val);
			t = dummyToken;
		}
		la = t;
	}
}

void Parser::Expect(int n) {
	if (la->kind==n) Get(); else { SynErr(n); }
}

void Parser::ExpectWeak(int n, int follow) {
	if (la->kind == n) Get();
	else {
		SynErr(n);
		while (!StartOf(follow)) Get();
	}
}

bool Parser::WeakSeparator(int n, int syFol, int repFol) {
	if (la->kind == n) {Get(); return true;}
	else if (StartOf(repFol)) {return false;}
	else {
		SynErr(n);
		while (!(StartOf(syFol) || StartOf(repFol) || StartOf(0))) {
			Get();
		}
		return StartOf(syFol);
	}
}

void Parser::BBCode() {
		this->first_list_element = false;
		this->str = L""; 
		this->mode = L""; 
		wstring tag;
		
		while (StartOf(1)) {
			if (la->kind == 4) {
				Get();
				this->str += L"["; 
			} else if (StartOf(2)) {
				BBTag(tag);
				this->str += tag; 
			} else {
				Get();
				this->str += this->t->val; 
			}
		}
		Expect(0);
		while(!this->tags.empty()){
		wstring elt = this->tags.back();
		this->tags.pop_back();
			this->str += L"</" + elt + L">";
		}
		   
		
}

void Parser::BBTag(wstring &tag) {
		switch (la->kind) {
		case 5: case 6: case 7: case 8: case 9: case 10: {
			URLTag(tag);
			break;
		}
		case 12: case 13: case 14: {
			ListTag(tag);
			break;
		}
		case 46: {
			IMGtag(tag);
			break;
		}
		case 15: case 16: case 17: case 18: case 19: case 20: case 21: case 22: case 23: {
			SimpleTag(tag);
			break;
		}
		case 24: case 25: case 26: case 27: case 28: case 29: case 30: case 31: case 32: case 33: case 34: case 35: case 36: case 37: case 38: case 39: case 40: case 41: case 42: case 43: case 44: case 45: {
			DecorationTag(tag);
			break;
		}
		case 48: case 49: {
			QuoteTag(tag);
			break;
		}
		default: SynErr(52); break;
		}
}

void Parser::URLTag(wstring &output) {
		wstring url, open_tag, close_tag; 
		if (la->kind == 5 || la->kind == 6 || la->kind == 7) {
			if (la->kind == 5) {
				Get();
				output = PopTag(L"a"); 
			} else if (la->kind == 6) {
				Get();
				output = PopTag(L"a"); 
			} else {
				Get();
				output = PopTag(L"a"); 
			}
		} else if (la->kind == 8 || la->kind == 9 || la->kind == 10) {
			if (la->kind == 8) {
				Get();
				open_tag = L"url"; 
			} else if (la->kind == 9) {
				Get();
				open_tag = L"user"; 
			} else {
				Get();
				open_tag = L"email"; 
			}
			if (la->kind == 50) {
				ATTR(url);
				if (open_tag == L"url" && valid_url(url)) {
				output = forumcode_url(url);
				} else if (open_tag == L"user"){ 
					output = forumcode_user(url);
				} else if (open_tag == L"email") {
					output = forumcode_email(url);
				} else {
					output = wstring(L"[") + open_tag + L"=" + url + close_tag + L"]";
				}
				
				
			} else if (la->kind == 11) {
				Get();
				url = L""; 
				while (StartOf(3)) {
					Get();
					url += this->t->val; 
				}
				if (la->kind == 5) {
					Get();
					close_tag = L"url";  
				} else if (la->kind == 6) {
					Get();
					close_tag = L"user";  
				} else if (la->kind == 7) {
					Get();
					close_tag = L"email"; 
				} else SynErr(53);
				if (open_tag == close_tag){
				if (open_tag == L"url" && valid_url(url)) {
					output = forumcode_url(url) + url;
					output += PopTag(L"a"); 
				} else if (open_tag == L"user") {
					output = forumcode_user(url) + url;
					output += PopTag(L"a");
				} else if (open_tag == L"email") {
					output = forumcode_email(url) + url;
					output += PopTag(L"a");
				} else {
					output = url;
				}
				}else{
					output = wstring(L"[") + open_tag + L"]" + url + L"[/" + close_tag + L"]";
				}
				
			} else SynErr(54);
		} else SynErr(55);
}

void Parser::ListTag(wstring &output) {
		output = L""; 
		wstring type = L""; 
		if (la->kind == 12) {
			Get();
			if (!this->tags.empty() && this->tags.back() == L"li"){
			output += PopTag(L"li");
			output += PushTag(L"li", L"");
			}else if (this->last_list != L""){
				output += PushTag(L"li", L"");
			}else{
				   	output += L"[*]";
					} 
				
		} else if (la->kind == 13) {
			Get();
			this->first_list_element = true; 
			if (la->kind == 50) {
				ATTR(type);
				output += PushTag(L"ol", wstring(L" type=\"") + type + L"\""); this->last_list = L"ol"; 
			} else if (la->kind == 11) {
				Get();
				output += PushTag(L"ul", L""); this->last_list = L"ul"; 
			} else SynErr(56);
			output += PushTag(L"li", L""); 
			Expect(12);
		} else if (la->kind == 14) {
			Get();
			output += PopTag(L"li");
			if (this->last_list != L"") {
				output += PopTag(this->last_list); 
				this->last_list = L"";
			} 
			
		} else SynErr(57);
}

void Parser::IMGtag(wstring &tag) {
		wstring url; bool closed = false; 
		Expect(46);
		if (la->kind == 11) {
			Get();
			url = L""; 
			if (la->kind == 2) {
				Get();
				url += this->t->val; 
				if (la->kind == 47) {
					Get();
					closed = true; 
				}
			}
			if (closed){
			if (valid_url(url)) 
				tag = forumcode_image(url);
			else
				tag = wstring(L"[img]") + url + L"[/img]";
			} else {
				tag = wstring(L"[img]") + url + L"";
			}
			
		} else if (la->kind == 50) {
			ATTR(url);
			if (valid_url(url)) 
			tag = forumcode_image(url);
			else
				tag = wstring(L"[img=") + url + L"]";
			
			
		} else SynErr(58);
}

void Parser::SimpleTag(wstring &output) {
		wstring a; 
		switch (la->kind) {
		case 15: {
			Get();
			output = L"<hr/>"; 
			break;
		}
		case 16: {
			Get();
			ATTR(a);
			output = PushTag(L"font", wstring(L" size=\"") + a + L"\""); 
			break;
		}
		case 17: case 18: {
			if (la->kind == 17) {
				Get();
			} else {
				Get();
			}
			ATTR(a);
			output = PushTag(L"span", wstring(L" style=\"color:") + a + L"\""); 
			break;
		}
		case 19: {
			Get();
			ATTR(a);
			output = PushTag(L"font", wstring(L" face=\"") + a + L"\""); 
			break;
		}
		case 20: {
			Get();
			output = PopTag(L"font"); 
			break;
		}
		case 21: {
			Get();
			output = PopTag(L"font"); 
			break;
		}
		case 22: {
			Get();
			break;
		}
		case 23: {
			Get();
			output = PopTag(L"span"); 
			break;
		}
		default: SynErr(59); break;
		}
}

void Parser::DecorationTag(wstring &tag) {
		switch (la->kind) {
		case 24: {
			Get();
			tag = PushTag(L"b"); 
			break;
		}
		case 25: {
			Get();
			tag = PushTag(L"i"); 
			break;
		}
		case 26: {
			Get();
			tag = PushTag(L"u"); 
			break;
		}
		case 27: {
			Get();
			tag = PushTag(L"pre"); 
			break;
		}
		case 28: {
			Get();
			tag = PushTag(L"center"); 
			break;
		}
		case 29: {
			Get();
			tag = PushTag(L"div", L" style=\"text-align:left\""); 
			break;
		}
		case 30: {
			Get();
			tag = PushTag(L"div", L" style=\"text-align:right\""); 
			break;
		}
		case 31: {
			Get();
			tag = PushTag(L"div", L" style=\"text-align:justify\""); 
			break;
		}
		case 32: {
			Get();
			tag = PushTag(L"sub"); 
			break;
		}
		case 33: {
			Get();
			tag = PushTag(L"sup"); 
			break;
		}
		case 34: {
			Get();
			tag = PushTag(L"strike"); 
			break;
		}
		case 35: {
			Get();
			tag = PopTag(L"sub"); 
			break;
		}
		case 36: {
			Get();
			tag = PopTag(L"sup"); 
			break;
		}
		case 37: {
			Get();
			tag = PopTag(L"strike"); 
			break;
		}
		case 38: {
			Get();
			tag = PopTag(L"b"); 
			break;
		}
		case 39: {
			Get();
			tag = PopTag(L"i"); 
			break;
		}
		case 40: {
			Get();
			tag = PopTag(L"u"); 
			break;
		}
		case 41: {
			Get();
			tag = PopTag(L"pre"); 
			break;
		}
		case 42: {
			Get();
			tag = PopTag(L"div"); 
			break;
		}
		case 43: {
			Get();
			tag = PopTag(L"div"); 
			break;
		}
		case 44: {
			Get();
			tag = PopTag(L"div"); 
			break;
		}
		case 45: {
			Get();
			tag = PopTag(L"center"); 
			break;
		}
		default: SynErr(60); break;
		}
}

void Parser::QuoteTag(wstring &tag) {
		wstring source; 
		if (la->kind == 48) {
			Get();
			tag = PopTag(L"div"); 
			tag += PopTag(L"div");
			
		} else if (la->kind == 49) {
			Get();
			tag = L"<br/>";
			tag += PushTag(L"div", L" class=\"quote\""); 
			if (la->kind == 50) {
				ATTR(source);
				tag += PushTag(L"div");
				tag += PushTag(L"i");
				tag += L"Originally posted by: ";
				tag += PushTag(L"b");
				tag += source;
				tag += PopTag(L"b");
				tag += PopTag(L"i"); 
				tag += PopTag(L"div");
				
				tag += PushTag(L"div");
				
			} else if (la->kind == 11) {
				Get();
				tag += PushTag(L"div"); 
			} else SynErr(61);
		} else SynErr(62);
}

void Parser::ATTR(wstring &tag) {
		Expect(50);
		tag = L""; 
		while (StartOf(4)) {
			Get();
			tag += gsub(wstring(this->t->val), wstring(L"\""), wstring(L"%22")); //.gsub('"',"%22"); 
			
		}
		Expect(11);
}



void Parser::Parse() {
	t = NULL;
	la = dummyToken = new Token();
	la->val = coco_string_create(L"Dummy Token");
	Get();
	BBCode();

	Expect(0);
}

Parser::Parser(Scanner *scanner) {
	maxT = 51;

	minErrDist = 2;
	errDist = minErrDist;
	this->scanner = scanner;
	errors = new Errors();
}

bool Parser::StartOf(int s) {
	const bool T = true;
	const bool x = false;

	static bool set[5][53] = {
		{T,x,x,x, x,x,x,x, x,x,x,x, x,x,x,x, x,x,x,x, x,x,x,x, x,x,x,x, x,x,x,x, x,x,x,x, x,x,x,x, x,x,x,x, x,x,x,x, x,x,x,x, x},
		{x,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, x},
		{x,x,x,x, x,T,T,T, T,T,T,x, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,x, T,T,x,x, x},
		{x,T,T,T, T,x,x,x, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, x},
		{x,T,T,T, T,T,T,T, T,T,T,x, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, T,T,T,T, x}
	};



	return set[s][la->kind];
}

Parser::~Parser() {
	delete errors;
	delete dummyToken;
}

Errors::Errors() {
	count = 0;
}

void Errors::SynErr(int line, int col, int n) {
	wchar_t* s;
	switch (n) {
			case 0: s = coco_string_create(L"EOF expected"); break;
			case 1: s = coco_string_create(L"whitespace expected"); break;
			case 2: s = coco_string_create(L"word expected"); break;
			case 3: s = coco_string_create(L"escapedslash expected"); break;
			case 4: s = coco_string_create(L"escapedbracket expected"); break;
			case 5: s = coco_string_create(L"\"[/url]\" expected"); break;
			case 6: s = coco_string_create(L"\"[/user]\" expected"); break;
			case 7: s = coco_string_create(L"\"[/email]\" expected"); break;
			case 8: s = coco_string_create(L"\"[url\" expected"); break;
			case 9: s = coco_string_create(L"\"[user\" expected"); break;
			case 10: s = coco_string_create(L"\"[email\" expected"); break;
			case 11: s = coco_string_create(L"\"]\" expected"); break;
			case 12: s = coco_string_create(L"\"[*]\" expected"); break;
			case 13: s = coco_string_create(L"\"[list\" expected"); break;
			case 14: s = coco_string_create(L"\"[/list]\" expected"); break;
			case 15: s = coco_string_create(L"\"[hr]\" expected"); break;
			case 16: s = coco_string_create(L"\"[size\" expected"); break;
			case 17: s = coco_string_create(L"\"[color\" expected"); break;
			case 18: s = coco_string_create(L"\"[colour\" expected"); break;
			case 19: s = coco_string_create(L"\"[font\" expected"); break;
			case 20: s = coco_string_create(L"\"[/font]\" expected"); break;
			case 21: s = coco_string_create(L"\"[/size]\" expected"); break;
			case 22: s = coco_string_create(L"\"[/colour]\" expected"); break;
			case 23: s = coco_string_create(L"\"[/color]\" expected"); break;
			case 24: s = coco_string_create(L"\"[b]\" expected"); break;
			case 25: s = coco_string_create(L"\"[i]\" expected"); break;
			case 26: s = coco_string_create(L"\"[u]\" expected"); break;
			case 27: s = coco_string_create(L"\"[code]\" expected"); break;
			case 28: s = coco_string_create(L"\"[center]\" expected"); break;
			case 29: s = coco_string_create(L"\"[left]\" expected"); break;
			case 30: s = coco_string_create(L"\"[right]\" expected"); break;
			case 31: s = coco_string_create(L"\"[justify]\" expected"); break;
			case 32: s = coco_string_create(L"\"[sub]\" expected"); break;
			case 33: s = coco_string_create(L"\"[sup]\" expected"); break;
			case 34: s = coco_string_create(L"\"[strike]\" expected"); break;
			case 35: s = coco_string_create(L"\"[/sub]\" expected"); break;
			case 36: s = coco_string_create(L"\"[/sup]\" expected"); break;
			case 37: s = coco_string_create(L"\"[/strike]\" expected"); break;
			case 38: s = coco_string_create(L"\"[/b]\" expected"); break;
			case 39: s = coco_string_create(L"\"[/i]\" expected"); break;
			case 40: s = coco_string_create(L"\"[/u]\" expected"); break;
			case 41: s = coco_string_create(L"\"[/code]\" expected"); break;
			case 42: s = coco_string_create(L"\"[/left]\" expected"); break;
			case 43: s = coco_string_create(L"\"[/right]\" expected"); break;
			case 44: s = coco_string_create(L"\"[/justify]\" expected"); break;
			case 45: s = coco_string_create(L"\"[/center]\" expected"); break;
			case 46: s = coco_string_create(L"\"[img\" expected"); break;
			case 47: s = coco_string_create(L"\"[/img]\" expected"); break;
			case 48: s = coco_string_create(L"\"[/quote]\" expected"); break;
			case 49: s = coco_string_create(L"\"[quote\" expected"); break;
			case 50: s = coco_string_create(L"\"=\" expected"); break;
			case 51: s = coco_string_create(L"??? expected"); break;
			case 52: s = coco_string_create(L"invalid BBTag"); break;
			case 53: s = coco_string_create(L"invalid URLTag"); break;
			case 54: s = coco_string_create(L"invalid URLTag"); break;
			case 55: s = coco_string_create(L"invalid URLTag"); break;
			case 56: s = coco_string_create(L"invalid ListTag"); break;
			case 57: s = coco_string_create(L"invalid ListTag"); break;
			case 58: s = coco_string_create(L"invalid IMGtag"); break;
			case 59: s = coco_string_create(L"invalid SimpleTag"); break;
			case 60: s = coco_string_create(L"invalid DecorationTag"); break;
			case 61: s = coco_string_create(L"invalid QuoteTag"); break;
			case 62: s = coco_string_create(L"invalid QuoteTag"); break;

		default:
		{
			wchar_t format[20];
			coco_swprintf(format, 20, L"error %d", n);
			s = coco_string_create(format);
		}
		break;
	}
	wprintf(L"-- line %d col %d: %ls\n", line, col, s);
	coco_string_delete(s);
	count++;
}

void Errors::Error(int line, int col, const wchar_t *s) {
	wprintf(L"-- line %d col %d: %ls\n", line, col, s);
	count++;
}

void Errors::Warning(int line, int col, const wchar_t *s) {
	wprintf(L"-- line %d col %d: %ls\n", line, col, s);
}

void Errors::Warning(const wchar_t *s) {
	wprintf(L"%ls\n", s);
}

void Errors::Exception(const wchar_t* s) {
	wprintf(L"%ls", s); 
	exit(1);
}

}; // namespace


