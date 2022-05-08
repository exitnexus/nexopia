class Proc #:nodoc:
  def bind(object)
  	$proc_bind_counter ||= 0
  	$proc_bind_counter += 1
    block = self
    (class << object; self end).class_eval do
      method_name = "__bind_#{$proc_bind_counter}"
      define_method(method_name, &block)
      method = instance_method(method_name)
      remove_method(method_name)
      method
    end.bind(object)
  end
end

class Object
  unless defined? instance_exec # 1.9
    def instance_exec(*arguments, &block)
      block.bind(self)[*arguments]
    end
  end
end