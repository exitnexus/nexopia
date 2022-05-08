require 'rexml/validation/validationexception'

module REXML
	module Parsers
		class TemplateTreeParser
			
			attr :document, true;
			attr :orig, true;
			
			def initialize( source, build_context = TemplateDocument.new )
				@orig = source;
				@build_context = build_context
				@document = build_context;
				@parser = Parsers::BaseParser.new( source )
			end
			
			def add_listener( listener )
				@parser.add_listener( listener )
			end
			
			def parse
				tag_stack = []
				in_doctype = false
				entities = nil
				begin
					while true
						event = @parser.pull
						current_index = @orig.index(@parser.source.buffer)
						line_num = @orig[0..current_index].count("\n");
						
						#@build_context.set_position(line_num)
						#@build_context.source = self;
						#$log.info("#{line_num}:#{@parser.source.position}")
						@current_position = line_num;
						#STDERR.puts "TemplateTreeParser GOT #{event.inspect}"
						case event[0]
						when :end_document
							unless tag_stack.empty?
								#raise ParseException.new("No close tag for #{tag_stack.inspect}")
								raise ParseException.new("No close tag for #{@build_context.xpath}")
							end
							return
						when :start_element
							tag_stack.push(event[1])
							# find the observers for namespaces
							@build_context = @build_context.add_element( event[1], event[2] )
							@document.positions[@build_context] = @current_position;
						when :end_element
							tag_stack.pop
							@build_context = @build_context.parent
						when :text
							if not in_doctype
								if @build_context[-1].instance_of? Text
									@build_context[-1] << event[1]
									@document.positions[event[1]] = @current_position;
								else
									if not (@build_context.ignore_whitespace_nodes and event[1].strip.size==0)
										el = @build_context.add( 
													Text.new(event[1], @build_context.whitespace, nil, true) 
										) 
										@document.positions[el] = @current_position;
									end
								end
							end
						when :comment
							c = Comment.new( event[1] )
							@build_context.add( c )
							@document.positions[c] = @current_position;
						when :cdata
							c = CData.new( event[1] )
							@build_context.add( c )
							@document.positions[c] = @current_position;
						when :processing_instruction
							el = @build_context.add( Instruction.new( event[1], event[2] ) )
							@document.positions[el] = @current_position;
						when :end_doctype
							in_doctype = false
							entities.each { |k,v| entities[k] = @build_context.entities[k].value }
							@build_context = @build_context.parent
						when :start_doctype
							doctype = DocType.new( event[1..-1], @build_context )
							@build_context = doctype
							entities = {}
							in_doctype = true
							@document.positions[doctype] = @current_position;
						when :attlistdecl
							n = AttlistDecl.new( event[1..-1] )
							@build_context.add( n )
							@document.positions[n] = @current_position;
						when :externalentity
							n = ExternalEntity.new( event[1] )
							@build_context.add( n )
							@document.positions[n] = @current_position;
						when :elementdecl
							n = ElementDecl.new( event[1] )
							@build_context.add(n)
							@document.positions[n] = @current_position;
						when :entitydecl
							entities[ event[1] ] = event[2] unless event[2] =~ /PUBLIC|SYSTEM/
							@build_context.add(Entity.new(event))
							@document.positions[n] = @current_position;
						when :notationdecl
							n = NotationDecl.new( *event[1..-1] )
							@build_context.add( n )
							@document.positions[n] = @current_position;
						when :xmldecl
							x = XMLDecl.new( event[1], event[2], event[3] )
							@build_context.add( x )
							@document.positions[n] = @current_position;
						end
					end
				rescue REXML::Validation::ValidationException
					raise
				rescue
					raise ParseException.new( $!.message, @parser.source, @parser, $! )
				end
			end
		end
	end
end
