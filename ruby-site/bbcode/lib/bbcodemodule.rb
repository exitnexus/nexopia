require 'bbcode'

UserContent::register_converter(:bbcode, BBCode::method(:parse), true, UserContent::ContentConverter::GENERATES_HTML)
