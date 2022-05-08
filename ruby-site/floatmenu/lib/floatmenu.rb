
class Floatmenu
	def initialize(title, body)
		t = Template.instance("floatmenu", "floatmenu");

        t.title = title
        t.body  = body

		puts t.display
	end
end
