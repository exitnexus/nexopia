require 'thread'
class MogileFS::Pool

  class BadObjectError < RuntimeError; end

  def initialize(klass, *args)
    @args = args
    @klass = klass
    @queue = Queue.new
    @objects = []
  end

  def get
    begin
      object = @queue.pop true
    rescue ThreadError
      object = @klass.new(*@args)
      @objects << object
    end
    return object
  end

  def put(o)
    raise BadObjectError unless @objects.include? o
    @queue.push o
    purge
  end

  def use
    object = get
    yield object
  ensure
    put object
  end

  def purge
    return if @queue.length < 5
    begin
      until @queue.length <= 2 do
        obj = @queue.pop true
        @objects.delete obj
      end
    rescue ThreadError
    end
  end

end

