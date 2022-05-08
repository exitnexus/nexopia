lib_require :Devutils, 'quiz'
require 'cobravsmongoose' # gem install cobravsmongoose && gem install json


class TestJsonToXML	 < Quiz
		
		def test_xml_to_json
			xml_string = "<a><b><c>taco bell</c><e tag='fish' /></b></a>"
			json_string = CobraVsMongoose.xml_to_json(xml_string)
			expected = "{\"a\":{\"b\":{\"c\":{\"$\":\"taco bell\"},\"e\":{\"@tag\":\"fish\"}}}}"
			assert_equal(expected,json_string)
			hash = CobraVsMongoose.xml_to_hash(xml_string)
			assert_equal(CobraVsMongoose.hash_to_xml(hash), CobraVsMongoose.json_to_xml(json_string))
		end
end