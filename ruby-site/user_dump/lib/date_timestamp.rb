class DateTimestamp < DateTime
	def to_i
		Time.local(self.year(), self.mon(), self.day(), self.hour(), self.min(), self.sec()).to_i
	end
end
