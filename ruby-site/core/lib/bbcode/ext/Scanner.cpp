

#include <memory.h>
#include <string.h>
#include "Scanner.h"

// string handling, wide character

wchar_t* coco_string_create(const wchar_t* value) {
	wchar_t* data;
	int len = 0;
	if (value) { len = wcslen(value); }
	data = new wchar_t[len + 1];
	wcsncpy(data, value, len);
	data[len] = 0;
	return data;
}

wchar_t* coco_string_create(const wchar_t *value , int startIndex, int length) {
	int len = 0;
	wchar_t* data;

	if (value) { len = length; }
	data = new wchar_t[len + 1];
	wcsncpy(data, &(value[startIndex]), len);
	data[len] = 0;

	return data;
}

wchar_t* coco_string_create_upper(const wchar_t* data) {
	if (!data) { return NULL; }

	int dataLen = 0;
	if (data) { dataLen = wcslen(data); }

	wchar_t *newData = new wchar_t[dataLen + 1];

	for (int i = 0; i <= dataLen; i++) {
		if ((L'a' <= data[i]) && (data[i] <= L'z')) {
			newData[i] = data[i] + (L'A' - L'a');
		}
		else { newData[i] = data[i]; }
	}

	newData[dataLen] = L'\0';
	return newData;
}

wchar_t* coco_string_create_lower(const wchar_t* data) {
	if (!data) { return NULL; }
	int dataLen = wcslen(data);
	return coco_string_create_lower(data, 0, dataLen);
}

wchar_t* coco_string_create_lower(const wchar_t* data, int startIndex, int dataLen) {
	if (!data) { return NULL; }

	wchar_t* newData = new wchar_t[dataLen + 1];

	for (int i = 0; i <= dataLen; i++) {
		wchar_t ch = data[startIndex + i];
		if ((L'A' <= ch) && (ch <= L'Z')) {
			newData[i] = ch - (L'A' - L'a');
		}
		else { newData[i] = ch; }
	}
	newData[dataLen] = L'\0';
	return newData;
}

wchar_t* coco_string_create_append(const wchar_t* data1, const wchar_t* data2) {
	wchar_t* data;
	int data1Len = 0;
	int data2Len = 0;

	if (data1) { data1Len = wcslen(data1); }
	if (data2) {data2Len = wcslen(data2); }

	data = new wchar_t[data1Len + data2Len + 1];

	if (data1) { wcscpy(data, data1); }
	if (data2) { wcscpy(data + data1Len, data2); }

	data[data1Len + data2Len] = 0;

	return data;
}

wchar_t* coco_string_create_append(const wchar_t *target, const wchar_t appendix) {
	int targetLen = coco_string_length(target);
	wchar_t* data = new wchar_t[targetLen + 2];
	wcsncpy(data, target, targetLen);
	data[targetLen] = appendix;
	data[targetLen + 1] = 0;
	return data;
}

void coco_string_delete(wchar_t* &data) {
	delete [] data;
	data = NULL;
}

int coco_string_length(const wchar_t* data) {
	if (data) { return wcslen(data); }
	return 0;
}

bool coco_string_endswith(const wchar_t* data, const wchar_t *end) {
	int dataLen = wcslen(data);
	int endLen = wcslen(end);
	return (endLen <= dataLen) && (wcscmp(data + dataLen - endLen, end) == 0);
}

int coco_string_indexof(const wchar_t* data, const wchar_t value) {
	const wchar_t* chr = wcschr(data, value);

	if (chr) { return (chr-data); }
	return -1;
}

int coco_string_lastindexof(const wchar_t* data, const wchar_t value) {
	const wchar_t* chr = wcsrchr(data, value);

	if (chr) { return (chr-data); }
	return -1;
}

void coco_string_merge(wchar_t* &target, const wchar_t* appendix) {
	if (!appendix) { return; }
	wchar_t* data = coco_string_create_append(target, appendix);
	delete [] target;
	target = data;
}

bool coco_string_equal(const wchar_t* data1, const wchar_t* data2) {
	return wcscmp( data1, data2 ) == 0;
}

int coco_string_compareto(const wchar_t* data1, const wchar_t* data2) {
	return wcscmp(data1, data2);
}

int coco_string_hash(const wchar_t *data) {
	int h = 0;
	if (!data) { return 0; }
	while (*data != 0) {
		h = (h * 7) ^ *data;
		++data;
	}
	if (h < 0) { h = -h; }
	return h;
}

// string handling, ascii character

wchar_t* coco_string_create(const char* value) {
	int len = 0;
	if (value) { len = strlen(value); }
	wchar_t* data = new wchar_t[len + 1];
	for (int i = 0; i < len; ++i) { data[i] = (wchar_t) value[i]; }
	data[len] = 0;
	return data;
}

char* coco_string_create_char(const wchar_t *value) {
	int len = coco_string_length(value);
	char *res = new char[len + 1];
	for (int i = 0; i < len; ++i) { res[i] = (char) value[i]; }
	res[len] = 0;
	return res;
}

void coco_string_delete(char* &data) {
	delete [] data;
	data = NULL;
}


namespace BBCode {


Token::Token() {
	kind = 0;
	pos  = 0;
	col  = 0;
	line = 0;
	val  = NULL;
	next = NULL;
}

Token::~Token() {
	coco_string_delete(val);
}


Buffer::Buffer(FILE* s, bool isUserStream) {
	stream = s; this->isUserStream = isUserStream;
	fseek(s, 0, SEEK_END);
	fileLen = bufLen = ftell(s);
	fseek(s, 0, SEEK_SET);
	buf = new char[MAX_BUFFER_LENGTH];
	bufStart = INT_MAX; // nothing in the buffer so far
	SetPos(0);          // setup  buffer to position 0 (start)
	if (bufLen == fileLen) Close();
}

Buffer::Buffer(Buffer *b) {
	buf = b->buf;
	b->buf = NULL;
	bufStart = b->bufStart;
	bufLen = b->bufLen;
	fileLen = b->fileLen;
	pos = b->pos;
	stream = b->stream;
	b->stream = NULL;
	isUserStream = b->isUserStream;
}

Buffer::Buffer(const char* buf, int len) {
	this->buf = new char[len];
	memcpy(this->buf, buf, len*sizeof(char));
	bufStart = 0;
	bufLen = len;
	fileLen = len;
	pos = 0;
	stream = NULL;
}

Buffer::~Buffer() {
	Close(); 
	if (buf != NULL) {
		delete [] buf;
		buf = NULL;
	}
}

void Buffer::Close() {
	if (!isUserStream && stream != NULL) {
		fclose(stream);
		stream = NULL;
	}
}

int Buffer::Read() {
	if (pos < bufLen) {
		return buf[pos++];
	} else if (GetPos() < fileLen) {
		SetPos(GetPos()); // shift buffer start to Pos
		return buf[pos++];
	} else {
		return EoF;
	}
}

int Buffer::Peek() {
	int curPos = GetPos();
	int ch = Read();
	SetPos(curPos);
	return ch;
}

char* Buffer::GetString(int beg, int end) {
	int len = end - beg;
	char *buf = new char[len];
	int oldPos = GetPos();
	SetPos(beg);
	for (int i = 0; i < len; ++i) buf[i] = (char) Read();
	SetPos(oldPos);
	return buf;
}

int Buffer::GetPos() {
	return pos + bufStart;
}

void Buffer::SetPos(int value) {
	if (value < 0) value = 0;
	else if (value > fileLen) value = fileLen;
	if (value >= bufStart && value < bufStart + bufLen) { // already in buffer
		pos = value - bufStart;
	} else if (stream != NULL) { // must be swapped in
		fseek(stream, value, SEEK_SET);
		bufLen = fread(buf, sizeof(char), MAX_BUFFER_LENGTH, stream);
		bufStart = value; pos = 0;
	} else {
		pos = fileLen - bufStart; // make Pos return fileLen
	}
}

int UTF8Buffer::Read() {
	int ch;
	do {
		ch = Buffer::Read();
		// until we find a uft8 start (0xxxxxxx or 11xxxxxx)
	} while ((ch >= 128) && ((ch & 0xC0) != 0xC0) && (ch != EOF));
	if (ch < 128 || ch == EOF) {
		// nothing to do, first 127 chars are the same in ascii and utf8
		// 0xxxxxxx or end of file character
	} else if ((ch & 0xF0) == 0xF0) {
		// 11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
		int c1 = ch & 0x07; ch = Buffer::Read();
		int c2 = ch & 0x3F; ch = Buffer::Read();
		int c3 = ch & 0x3F; ch = Buffer::Read();
		int c4 = ch & 0x3F;
		ch = (((((c1 << 6) | c2) << 6) | c3) << 6) | c4;
	} else if ((ch & 0xE0) == 0xE0) {
		// 1110xxxx 10xxxxxx 10xxxxxx
		int c1 = ch & 0x0F; ch = Buffer::Read();
		int c2 = ch & 0x3F; ch = Buffer::Read();
		int c3 = ch & 0x3F;
		ch = (((c1 << 6) | c2) << 6) | c3;
	} else if ((ch & 0xC0) == 0xC0) {
		// 110xxxxx 10xxxxxx
		int c1 = ch & 0x1F; ch = Buffer::Read();
		int c2 = ch & 0x3F;
		ch = (c1 << 6) | c2;
	}
	return ch;
}

Scanner::Scanner(const char* buf, int len) {
	buffer = new Buffer(buf, len);
	Init();
}

Scanner::Scanner(const wchar_t* fileName) {
	FILE* stream;
	char *chFileName = coco_string_create_char(fileName);
	if ((stream = fopen(chFileName, "rb")) == NULL) {
		wprintf(L"--- Cannot open file %ls\n", fileName);
		exit(1);
	}
	coco_string_delete(chFileName);
	buffer = new Buffer(stream, false);
	Init();
}
	
Scanner::Scanner(FILE* s) {
	buffer = new Buffer(s, true);
	Init();
}

Scanner::~Scanner() {
	char* cur = (char*) firstHeap;

	while(cur != NULL) {
		cur = *(char**) (cur + HEAP_BLOCK_SIZE);
		free(firstHeap);
		firstHeap = cur;
	}
	delete [] tval;
	delete buffer;
}

void Scanner::Init() {
	EOL    = '\n';
	eofSym = 0;
	maxT = 51;
	noSym = 51;
	int i;
	for (i = 9; i <= 10; ++i) start.set(i, 1);
	for (i = 13; i <= 13; ++i) start.set(i, 1);
	for (i = 32; i <= 32; ++i) start.set(i, 1);
	for (i = 0; i <= 8; ++i) start.set(i, 2);
	for (i = 11; i <= 12; ++i) start.set(i, 2);
	for (i = 14; i <= 31; ++i) start.set(i, 2);
	for (i = 33; i <= 60; ++i) start.set(i, 2);
	for (i = 62; i <= 90; ++i) start.set(i, 2);
	for (i = 94; i <= 65535; ++i) start.set(i, 2);
	start.set(92, 5);
	start.set(91, 165);
	start.set(93, 29);
	start.set(61, 164);
		start.set(Buffer::EoF, -1);


	tvalLength = 128;
	tval = new wchar_t[tvalLength]; // text of current token

	// HEAP_BLOCK_SIZE byte heap + pointer to next heap block
	heap = malloc(HEAP_BLOCK_SIZE + sizeof(void*));
	firstHeap = heap;
	heapEnd = (void**) (((char*) heap) + HEAP_BLOCK_SIZE);
	*heapEnd = 0;
	heapTop = heap;
	if (sizeof(Token) > HEAP_BLOCK_SIZE) {
		wprintf(L"--- Too small HEAP_BLOCK_SIZE\n");
		exit(1);
	}

	pos = -1; line = 1; col = 0;
	oldEols = 0;
	NextCh();
	if (ch == 0xEF) { // check optional byte order mark for UTF-8
		NextCh(); int ch1 = ch;
		NextCh(); int ch2 = ch;
		if (ch1 != 0xBB || ch2 != 0xBF) {
			wprintf(L"Illegal byte order mark at start of file");
			exit(1);
		}
		Buffer *oldBuf = buffer;
		buffer = new UTF8Buffer(buffer); col = 0;
		delete oldBuf; oldBuf = NULL;
		NextCh();
	}


	pt = tokens = CreateToken(); // first token is a dummy
}

void Scanner::NextCh() {
	if (oldEols > 0) { ch = EOL; oldEols--; } 
	else {
		pos = buffer->GetPos();
		ch = buffer->Read(); col++;
		// replace isolated '\r' by '\n' in order to make
		// eol handling uniform across Windows, Unix and Mac
		if (ch == L'\r' && buffer->Peek() != L'\n') ch = EOL;
		if (ch == EOL) { line++; col = 0; }
	}
		valCh = ch;
		if ('A' <= ch && ch <= 'Z') ch = ch - 'A' + 'a'; // ch.ToLower()
}

void Scanner::AddCh() {
	if (tlen >= tvalLength) {
		tvalLength *= 2;
		wchar_t *newBuf = new wchar_t[tvalLength];
		memcpy(newBuf, tval, tlen*sizeof(wchar_t));
		delete tval;
		tval = newBuf;
	}
	tval[tlen++] = valCh;
	NextCh();
}



void Scanner::CreateHeapBlock() {
	void* newHeap;
	char* cur = (char*) firstHeap;

	while(((char*) tokens < cur) || ((char*) tokens > (cur + HEAP_BLOCK_SIZE))) {
		cur = *((char**) (cur + HEAP_BLOCK_SIZE));
		free(firstHeap);
		firstHeap = cur;
	}

	// HEAP_BLOCK_SIZE byte heap + pointer to next heap block
	newHeap = malloc(HEAP_BLOCK_SIZE + sizeof(void*));
	*heapEnd = newHeap;
	heapEnd = (void**) (((char*) newHeap) + HEAP_BLOCK_SIZE);
	*heapEnd = 0;
	heap = newHeap;
	heapTop = heap;
}

Token* Scanner::CreateToken() {
	Token *t;
	if (((char*) heapTop + (int) sizeof(Token)) >= (char*) heapEnd) {
		CreateHeapBlock();
	}
	t = (Token*) heapTop;
	heapTop = (void*) ((char*) heapTop + sizeof(Token));
	t->val = NULL;
	t->next = NULL;
	return t;
}

void Scanner::AppendVal(Token *t) {
	int reqMem = (tlen + 1) * sizeof(wchar_t);
	if (((char*) heapTop + reqMem) >= (char*) heapEnd) {
		if (reqMem > HEAP_BLOCK_SIZE) {
			wprintf(L"--- Too long token value\n");
			exit(1);
		}
		CreateHeapBlock();
	}
	t->val = (wchar_t*) heapTop;
	heapTop = (void*) ((char*) heapTop + reqMem);

	wcsncpy(t->val, tval, tlen);
	t->val[tlen] = L'\0';
}

Token* Scanner::NextToken() {
	while (
			false
	) NextCh();

	t = CreateToken();
	t->pos = pos; t->col = col; t->line = line; 
	int state = start.state(ch);
	tlen = 0; AddCh();

	switch (state) {
		case -1: { t->kind = eofSym; break; } // NextCh already done
		case 0: { t->kind = noSym; break; }   // NextCh already done
		case 1:
			{t->kind = 1; break;}
		case 2:
			case_2:
			if (ch <= 8 || ch >= 11 && ch <= 12 || ch >= 14 && ch <= 31 || ch >= L'!' && ch <= L'<' || ch >= L'>' && ch <= L'Z' || ch >= L'^' && ch <= 65535) {AddCh(); goto case_2;}
			else {t->kind = 2; break;}
		case 3:
			case_3:
			{t->kind = 3; break;}
		case 4:
			case_4:
			{t->kind = 4; break;}
		case 5:
			if (ch == 92) {AddCh(); goto case_3;}
			else if (ch == L'[') {AddCh(); goto case_4;}
			else {t->kind = noSym; break;}
		case 6:
			case_6:
			if (ch == L'l') {AddCh(); goto case_7;}
			else {t->kind = noSym; break;}
		case 7:
			case_7:
			if (ch == L']') {AddCh(); goto case_8;}
			else {t->kind = noSym; break;}
		case 8:
			case_8:
			{t->kind = 5; break;}
		case 9:
			case_9:
			if (ch == L'e') {AddCh(); goto case_10;}
			else {t->kind = noSym; break;}
		case 10:
			case_10:
			if (ch == L'r') {AddCh(); goto case_11;}
			else {t->kind = noSym; break;}
		case 11:
			case_11:
			if (ch == L']') {AddCh(); goto case_12;}
			else {t->kind = noSym; break;}
		case 12:
			case_12:
			{t->kind = 6; break;}
		case 13:
			case_13:
			if (ch == L'm') {AddCh(); goto case_14;}
			else {t->kind = noSym; break;}
		case 14:
			case_14:
			if (ch == L'a') {AddCh(); goto case_15;}
			else {t->kind = noSym; break;}
		case 15:
			case_15:
			if (ch == L'i') {AddCh(); goto case_16;}
			else {t->kind = noSym; break;}
		case 16:
			case_16:
			if (ch == L'l') {AddCh(); goto case_17;}
			else {t->kind = noSym; break;}
		case 17:
			case_17:
			if (ch == L']') {AddCh(); goto case_18;}
			else {t->kind = noSym; break;}
		case 18:
			case_18:
			{t->kind = 7; break;}
		case 19:
			case_19:
			if (ch == L'l') {AddCh(); goto case_20;}
			else {t->kind = noSym; break;}
		case 20:
			case_20:
			{t->kind = 8; break;}
		case 21:
			case_21:
			if (ch == L'e') {AddCh(); goto case_22;}
			else {t->kind = noSym; break;}
		case 22:
			case_22:
			if (ch == L'r') {AddCh(); goto case_23;}
			else {t->kind = noSym; break;}
		case 23:
			case_23:
			{t->kind = 9; break;}
		case 24:
			case_24:
			if (ch == L'm') {AddCh(); goto case_25;}
			else {t->kind = noSym; break;}
		case 25:
			case_25:
			if (ch == L'a') {AddCh(); goto case_26;}
			else {t->kind = noSym; break;}
		case 26:
			case_26:
			if (ch == L'i') {AddCh(); goto case_27;}
			else {t->kind = noSym; break;}
		case 27:
			case_27:
			if (ch == L'l') {AddCh(); goto case_28;}
			else {t->kind = noSym; break;}
		case 28:
			case_28:
			{t->kind = 10; break;}
		case 29:
			{t->kind = 11; break;}
		case 30:
			case_30:
			if (ch == L']') {AddCh(); goto case_31;}
			else {t->kind = noSym; break;}
		case 31:
			case_31:
			{t->kind = 12; break;}
		case 32:
			case_32:
			if (ch == L's') {AddCh(); goto case_33;}
			else {t->kind = noSym; break;}
		case 33:
			case_33:
			if (ch == L't') {AddCh(); goto case_34;}
			else {t->kind = noSym; break;}
		case 34:
			case_34:
			{t->kind = 13; break;}
		case 35:
			case_35:
			if (ch == L's') {AddCh(); goto case_36;}
			else {t->kind = noSym; break;}
		case 36:
			case_36:
			if (ch == L't') {AddCh(); goto case_37;}
			else {t->kind = noSym; break;}
		case 37:
			case_37:
			if (ch == L']') {AddCh(); goto case_38;}
			else {t->kind = noSym; break;}
		case 38:
			case_38:
			{t->kind = 14; break;}
		case 39:
			case_39:
			if (ch == L'r') {AddCh(); goto case_40;}
			else {t->kind = noSym; break;}
		case 40:
			case_40:
			if (ch == L']') {AddCh(); goto case_41;}
			else {t->kind = noSym; break;}
		case 41:
			case_41:
			{t->kind = 15; break;}
		case 42:
			case_42:
			if (ch == L'z') {AddCh(); goto case_43;}
			else {t->kind = noSym; break;}
		case 43:
			case_43:
			if (ch == L'e') {AddCh(); goto case_44;}
			else {t->kind = noSym; break;}
		case 44:
			case_44:
			{t->kind = 16; break;}
		case 45:
			case_45:
			{t->kind = 17; break;}
		case 46:
			case_46:
			if (ch == L'r') {AddCh(); goto case_47;}
			else {t->kind = noSym; break;}
		case 47:
			case_47:
			{t->kind = 18; break;}
		case 48:
			case_48:
			if (ch == L'o') {AddCh(); goto case_49;}
			else {t->kind = noSym; break;}
		case 49:
			case_49:
			if (ch == L'n') {AddCh(); goto case_50;}
			else {t->kind = noSym; break;}
		case 50:
			case_50:
			if (ch == L't') {AddCh(); goto case_51;}
			else {t->kind = noSym; break;}
		case 51:
			case_51:
			{t->kind = 19; break;}
		case 52:
			case_52:
			if (ch == L'o') {AddCh(); goto case_53;}
			else {t->kind = noSym; break;}
		case 53:
			case_53:
			if (ch == L'n') {AddCh(); goto case_54;}
			else {t->kind = noSym; break;}
		case 54:
			case_54:
			if (ch == L't') {AddCh(); goto case_55;}
			else {t->kind = noSym; break;}
		case 55:
			case_55:
			if (ch == L']') {AddCh(); goto case_56;}
			else {t->kind = noSym; break;}
		case 56:
			case_56:
			{t->kind = 20; break;}
		case 57:
			case_57:
			if (ch == L'z') {AddCh(); goto case_58;}
			else {t->kind = noSym; break;}
		case 58:
			case_58:
			if (ch == L'e') {AddCh(); goto case_59;}
			else {t->kind = noSym; break;}
		case 59:
			case_59:
			if (ch == L']') {AddCh(); goto case_60;}
			else {t->kind = noSym; break;}
		case 60:
			case_60:
			{t->kind = 21; break;}
		case 61:
			case_61:
			if (ch == L'r') {AddCh(); goto case_62;}
			else {t->kind = noSym; break;}
		case 62:
			case_62:
			if (ch == L']') {AddCh(); goto case_63;}
			else {t->kind = noSym; break;}
		case 63:
			case_63:
			{t->kind = 22; break;}
		case 64:
			case_64:
			if (ch == L']') {AddCh(); goto case_65;}
			else {t->kind = noSym; break;}
		case 65:
			case_65:
			{t->kind = 23; break;}
		case 66:
			case_66:
			if (ch == L']') {AddCh(); goto case_67;}
			else {t->kind = noSym; break;}
		case 67:
			case_67:
			{t->kind = 24; break;}
		case 68:
			case_68:
			{t->kind = 25; break;}
		case 69:
			case_69:
			{t->kind = 26; break;}
		case 70:
			case_70:
			if (ch == L'e') {AddCh(); goto case_71;}
			else {t->kind = noSym; break;}
		case 71:
			case_71:
			if (ch == L']') {AddCh(); goto case_72;}
			else {t->kind = noSym; break;}
		case 72:
			case_72:
			{t->kind = 27; break;}
		case 73:
			case_73:
			if (ch == L'n') {AddCh(); goto case_74;}
			else {t->kind = noSym; break;}
		case 74:
			case_74:
			if (ch == L't') {AddCh(); goto case_75;}
			else {t->kind = noSym; break;}
		case 75:
			case_75:
			if (ch == L'e') {AddCh(); goto case_76;}
			else {t->kind = noSym; break;}
		case 76:
			case_76:
			if (ch == L'r') {AddCh(); goto case_77;}
			else {t->kind = noSym; break;}
		case 77:
			case_77:
			if (ch == L']') {AddCh(); goto case_78;}
			else {t->kind = noSym; break;}
		case 78:
			case_78:
			{t->kind = 28; break;}
		case 79:
			case_79:
			if (ch == L'f') {AddCh(); goto case_80;}
			else {t->kind = noSym; break;}
		case 80:
			case_80:
			if (ch == L't') {AddCh(); goto case_81;}
			else {t->kind = noSym; break;}
		case 81:
			case_81:
			if (ch == L']') {AddCh(); goto case_82;}
			else {t->kind = noSym; break;}
		case 82:
			case_82:
			{t->kind = 29; break;}
		case 83:
			case_83:
			if (ch == L'i') {AddCh(); goto case_84;}
			else {t->kind = noSym; break;}
		case 84:
			case_84:
			if (ch == L'g') {AddCh(); goto case_85;}
			else {t->kind = noSym; break;}
		case 85:
			case_85:
			if (ch == L'h') {AddCh(); goto case_86;}
			else {t->kind = noSym; break;}
		case 86:
			case_86:
			if (ch == L't') {AddCh(); goto case_87;}
			else {t->kind = noSym; break;}
		case 87:
			case_87:
			if (ch == L']') {AddCh(); goto case_88;}
			else {t->kind = noSym; break;}
		case 88:
			case_88:
			{t->kind = 30; break;}
		case 89:
			case_89:
			if (ch == L'u') {AddCh(); goto case_90;}
			else {t->kind = noSym; break;}
		case 90:
			case_90:
			if (ch == L's') {AddCh(); goto case_91;}
			else {t->kind = noSym; break;}
		case 91:
			case_91:
			if (ch == L't') {AddCh(); goto case_92;}
			else {t->kind = noSym; break;}
		case 92:
			case_92:
			if (ch == L'i') {AddCh(); goto case_93;}
			else {t->kind = noSym; break;}
		case 93:
			case_93:
			if (ch == L'f') {AddCh(); goto case_94;}
			else {t->kind = noSym; break;}
		case 94:
			case_94:
			if (ch == L'y') {AddCh(); goto case_95;}
			else {t->kind = noSym; break;}
		case 95:
			case_95:
			if (ch == L']') {AddCh(); goto case_96;}
			else {t->kind = noSym; break;}
		case 96:
			case_96:
			{t->kind = 31; break;}
		case 97:
			case_97:
			if (ch == L']') {AddCh(); goto case_98;}
			else {t->kind = noSym; break;}
		case 98:
			case_98:
			{t->kind = 32; break;}
		case 99:
			case_99:
			if (ch == L']') {AddCh(); goto case_100;}
			else {t->kind = noSym; break;}
		case 100:
			case_100:
			{t->kind = 33; break;}
		case 101:
			case_101:
			if (ch == L'r') {AddCh(); goto case_102;}
			else {t->kind = noSym; break;}
		case 102:
			case_102:
			if (ch == L'i') {AddCh(); goto case_103;}
			else {t->kind = noSym; break;}
		case 103:
			case_103:
			if (ch == L'k') {AddCh(); goto case_104;}
			else {t->kind = noSym; break;}
		case 104:
			case_104:
			if (ch == L'e') {AddCh(); goto case_105;}
			else {t->kind = noSym; break;}
		case 105:
			case_105:
			if (ch == L']') {AddCh(); goto case_106;}
			else {t->kind = noSym; break;}
		case 106:
			case_106:
			{t->kind = 34; break;}
		case 107:
			case_107:
			if (ch == L']') {AddCh(); goto case_108;}
			else {t->kind = noSym; break;}
		case 108:
			case_108:
			{t->kind = 35; break;}
		case 109:
			case_109:
			if (ch == L']') {AddCh(); goto case_110;}
			else {t->kind = noSym; break;}
		case 110:
			case_110:
			{t->kind = 36; break;}
		case 111:
			case_111:
			if (ch == L'r') {AddCh(); goto case_112;}
			else {t->kind = noSym; break;}
		case 112:
			case_112:
			if (ch == L'i') {AddCh(); goto case_113;}
			else {t->kind = noSym; break;}
		case 113:
			case_113:
			if (ch == L'k') {AddCh(); goto case_114;}
			else {t->kind = noSym; break;}
		case 114:
			case_114:
			if (ch == L'e') {AddCh(); goto case_115;}
			else {t->kind = noSym; break;}
		case 115:
			case_115:
			if (ch == L']') {AddCh(); goto case_116;}
			else {t->kind = noSym; break;}
		case 116:
			case_116:
			{t->kind = 37; break;}
		case 117:
			case_117:
			if (ch == L']') {AddCh(); goto case_118;}
			else {t->kind = noSym; break;}
		case 118:
			case_118:
			{t->kind = 38; break;}
		case 119:
			case_119:
			{t->kind = 39; break;}
		case 120:
			case_120:
			{t->kind = 40; break;}
		case 121:
			case_121:
			if (ch == L'e') {AddCh(); goto case_122;}
			else {t->kind = noSym; break;}
		case 122:
			case_122:
			if (ch == L']') {AddCh(); goto case_123;}
			else {t->kind = noSym; break;}
		case 123:
			case_123:
			{t->kind = 41; break;}
		case 124:
			case_124:
			if (ch == L'f') {AddCh(); goto case_125;}
			else {t->kind = noSym; break;}
		case 125:
			case_125:
			if (ch == L't') {AddCh(); goto case_126;}
			else {t->kind = noSym; break;}
		case 126:
			case_126:
			if (ch == L']') {AddCh(); goto case_127;}
			else {t->kind = noSym; break;}
		case 127:
			case_127:
			{t->kind = 42; break;}
		case 128:
			case_128:
			if (ch == L'i') {AddCh(); goto case_129;}
			else {t->kind = noSym; break;}
		case 129:
			case_129:
			if (ch == L'g') {AddCh(); goto case_130;}
			else {t->kind = noSym; break;}
		case 130:
			case_130:
			if (ch == L'h') {AddCh(); goto case_131;}
			else {t->kind = noSym; break;}
		case 131:
			case_131:
			if (ch == L't') {AddCh(); goto case_132;}
			else {t->kind = noSym; break;}
		case 132:
			case_132:
			if (ch == L']') {AddCh(); goto case_133;}
			else {t->kind = noSym; break;}
		case 133:
			case_133:
			{t->kind = 43; break;}
		case 134:
			case_134:
			if (ch == L'u') {AddCh(); goto case_135;}
			else {t->kind = noSym; break;}
		case 135:
			case_135:
			if (ch == L's') {AddCh(); goto case_136;}
			else {t->kind = noSym; break;}
		case 136:
			case_136:
			if (ch == L't') {AddCh(); goto case_137;}
			else {t->kind = noSym; break;}
		case 137:
			case_137:
			if (ch == L'i') {AddCh(); goto case_138;}
			else {t->kind = noSym; break;}
		case 138:
			case_138:
			if (ch == L'f') {AddCh(); goto case_139;}
			else {t->kind = noSym; break;}
		case 139:
			case_139:
			if (ch == L'y') {AddCh(); goto case_140;}
			else {t->kind = noSym; break;}
		case 140:
			case_140:
			if (ch == L']') {AddCh(); goto case_141;}
			else {t->kind = noSym; break;}
		case 141:
			case_141:
			{t->kind = 44; break;}
		case 142:
			case_142:
			if (ch == L'n') {AddCh(); goto case_143;}
			else {t->kind = noSym; break;}
		case 143:
			case_143:
			if (ch == L't') {AddCh(); goto case_144;}
			else {t->kind = noSym; break;}
		case 144:
			case_144:
			if (ch == L'e') {AddCh(); goto case_145;}
			else {t->kind = noSym; break;}
		case 145:
			case_145:
			if (ch == L'r') {AddCh(); goto case_146;}
			else {t->kind = noSym; break;}
		case 146:
			case_146:
			if (ch == L']') {AddCh(); goto case_147;}
			else {t->kind = noSym; break;}
		case 147:
			case_147:
			{t->kind = 45; break;}
		case 148:
			case_148:
			if (ch == L'g') {AddCh(); goto case_149;}
			else {t->kind = noSym; break;}
		case 149:
			case_149:
			{t->kind = 46; break;}
		case 150:
			case_150:
			if (ch == L'g') {AddCh(); goto case_151;}
			else {t->kind = noSym; break;}
		case 151:
			case_151:
			if (ch == L']') {AddCh(); goto case_152;}
			else {t->kind = noSym; break;}
		case 152:
			case_152:
			{t->kind = 47; break;}
		case 153:
			case_153:
			if (ch == L'u') {AddCh(); goto case_154;}
			else {t->kind = noSym; break;}
		case 154:
			case_154:
			if (ch == L'o') {AddCh(); goto case_155;}
			else {t->kind = noSym; break;}
		case 155:
			case_155:
			if (ch == L't') {AddCh(); goto case_156;}
			else {t->kind = noSym; break;}
		case 156:
			case_156:
			if (ch == L'e') {AddCh(); goto case_157;}
			else {t->kind = noSym; break;}
		case 157:
			case_157:
			if (ch == L']') {AddCh(); goto case_158;}
			else {t->kind = noSym; break;}
		case 158:
			case_158:
			{t->kind = 48; break;}
		case 159:
			case_159:
			if (ch == L'u') {AddCh(); goto case_160;}
			else {t->kind = noSym; break;}
		case 160:
			case_160:
			if (ch == L'o') {AddCh(); goto case_161;}
			else {t->kind = noSym; break;}
		case 161:
			case_161:
			if (ch == L't') {AddCh(); goto case_162;}
			else {t->kind = noSym; break;}
		case 162:
			case_162:
			if (ch == L'e') {AddCh(); goto case_163;}
			else {t->kind = noSym; break;}
		case 163:
			case_163:
			{t->kind = 49; break;}
		case 164:
			{t->kind = 50; break;}
		case 165:
			if (ch == L'/') {AddCh(); goto case_166;}
			else if (ch == L'u') {AddCh(); goto case_167;}
			else if (ch == L'e') {AddCh(); goto case_24;}
			else if (ch == L'*') {AddCh(); goto case_30;}
			else if (ch == L'l') {AddCh(); goto case_168;}
			else if (ch == L'h') {AddCh(); goto case_39;}
			else if (ch == L's') {AddCh(); goto case_169;}
			else if (ch == L'c') {AddCh(); goto case_170;}
			else if (ch == L'f') {AddCh(); goto case_48;}
			else if (ch == L'b') {AddCh(); goto case_66;}
			else if (ch == L'i') {AddCh(); goto case_171;}
			else if (ch == L'r') {AddCh(); goto case_83;}
			else if (ch == L'j') {AddCh(); goto case_89;}
			else if (ch == L'q') {AddCh(); goto case_159;}
			else {t->kind = noSym; break;}
		case 166:
			case_166:
			if (ch == L'u') {AddCh(); goto case_172;}
			else if (ch == L'e') {AddCh(); goto case_13;}
			else if (ch == L'l') {AddCh(); goto case_173;}
			else if (ch == L'f') {AddCh(); goto case_52;}
			else if (ch == L's') {AddCh(); goto case_174;}
			else if (ch == L'c') {AddCh(); goto case_175;}
			else if (ch == L'b') {AddCh(); goto case_117;}
			else if (ch == L'i') {AddCh(); goto case_176;}
			else if (ch == L'r') {AddCh(); goto case_128;}
			else if (ch == L'j') {AddCh(); goto case_134;}
			else if (ch == L'q') {AddCh(); goto case_153;}
			else {t->kind = noSym; break;}
		case 167:
			case_167:
			if (ch == L'r') {AddCh(); goto case_19;}
			else if (ch == L's') {AddCh(); goto case_21;}
			else if (ch == L']') {AddCh(); goto case_69;}
			else {t->kind = noSym; break;}
		case 168:
			case_168:
			if (ch == L'i') {AddCh(); goto case_32;}
			else if (ch == L'e') {AddCh(); goto case_79;}
			else {t->kind = noSym; break;}
		case 169:
			case_169:
			if (ch == L'i') {AddCh(); goto case_42;}
			else if (ch == L'u') {AddCh(); goto case_177;}
			else if (ch == L't') {AddCh(); goto case_101;}
			else {t->kind = noSym; break;}
		case 170:
			case_170:
			if (ch == L'o') {AddCh(); goto case_178;}
			else if (ch == L'e') {AddCh(); goto case_73;}
			else {t->kind = noSym; break;}
		case 171:
			case_171:
			if (ch == L']') {AddCh(); goto case_68;}
			else if (ch == L'm') {AddCh(); goto case_148;}
			else {t->kind = noSym; break;}
		case 172:
			case_172:
			if (ch == L'r') {AddCh(); goto case_6;}
			else if (ch == L's') {AddCh(); goto case_9;}
			else if (ch == L']') {AddCh(); goto case_120;}
			else {t->kind = noSym; break;}
		case 173:
			case_173:
			if (ch == L'i') {AddCh(); goto case_35;}
			else if (ch == L'e') {AddCh(); goto case_124;}
			else {t->kind = noSym; break;}
		case 174:
			case_174:
			if (ch == L'i') {AddCh(); goto case_57;}
			else if (ch == L'u') {AddCh(); goto case_179;}
			else if (ch == L't') {AddCh(); goto case_111;}
			else {t->kind = noSym; break;}
		case 175:
			case_175:
			if (ch == L'o') {AddCh(); goto case_180;}
			else if (ch == L'e') {AddCh(); goto case_142;}
			else {t->kind = noSym; break;}
		case 176:
			case_176:
			if (ch == L']') {AddCh(); goto case_119;}
			else if (ch == L'm') {AddCh(); goto case_150;}
			else {t->kind = noSym; break;}
		case 177:
			case_177:
			if (ch == L'b') {AddCh(); goto case_97;}
			else if (ch == L'p') {AddCh(); goto case_99;}
			else {t->kind = noSym; break;}
		case 178:
			case_178:
			if (ch == L'l') {AddCh(); goto case_181;}
			else if (ch == L'd') {AddCh(); goto case_70;}
			else {t->kind = noSym; break;}
		case 179:
			case_179:
			if (ch == L'b') {AddCh(); goto case_107;}
			else if (ch == L'p') {AddCh(); goto case_109;}
			else {t->kind = noSym; break;}
		case 180:
			case_180:
			if (ch == L'l') {AddCh(); goto case_182;}
			else if (ch == L'd') {AddCh(); goto case_121;}
			else {t->kind = noSym; break;}
		case 181:
			case_181:
			if (ch == L'o') {AddCh(); goto case_183;}
			else {t->kind = noSym; break;}
		case 182:
			case_182:
			if (ch == L'o') {AddCh(); goto case_184;}
			else {t->kind = noSym; break;}
		case 183:
			case_183:
			if (ch == L'r') {AddCh(); goto case_45;}
			else if (ch == L'u') {AddCh(); goto case_46;}
			else {t->kind = noSym; break;}
		case 184:
			case_184:
			if (ch == L'u') {AddCh(); goto case_61;}
			else if (ch == L'r') {AddCh(); goto case_64;}
			else {t->kind = noSym; break;}

	}
	AppendVal(t);
	return t;
}

// get the next token (possibly a token already seen during peeking)
Token* Scanner::Scan() {
	if (tokens->next == NULL) {
		return pt = tokens = NextToken();
	} else {
		pt = tokens = tokens->next;
		return tokens;
	}
}

// peek for the next token, ignore pragmas
Token* Scanner::Peek() {
	if (pt->next == NULL) {
		do {
			pt = pt->next = NextToken();
		} while (pt->kind > maxT); // skip pragmas
	} else {
		do {
			pt = pt->next; 
		} while (pt->kind > maxT);
	}
	return pt;
}

// make sure that peeking starts at the current scan position
void Scanner::ResetPeek() {
	pt = tokens;
}

}; // namespace


