lib_require :Core, 'typeid'
lib_require :Metrics, 'metric_category'

module Metrics
	class CategoryPlusSales < MetricCategory
		extend TypeID
		
		metric_category
		
		CONVERSION_RATE = 1
		GROSS_INCOME    = 2
		
		def initialize()
			super()
			
			@metrics[CONVERSION_RATE] = {
				:description => "Conversion Rate",
				:header => "Percent",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
			@metrics[GROSS_INCOME] = {
				:description => "Gross Income",
				:header => "Dollars",
				:subheaders => [],
				:usertypes => false,
				:allow_historical => true
			}
		end
		
		def self.description()
			return "Plus Sales"
		end
		
		def subheaders(metric)
			if (metric == GROSS_INCOME)
				retval = [
					'Cash',
					'Cheque',
					'Refund (Paypal)',
					'Money Order',
					'Debit',
					'EMT',
					'PIN/Voucher',
					'Visa',
					'MC',
					'Billing People',
					'PaymentPin mobile',
					'PaymentPin landline',
					'Other/unknown'
					]
			else
				retval = super(metric)
			end
			
			return retval
		end
		
		def populate(metrics, date, historical)
			metrics = [*metrics]
			day_from, day_to = get_date_from_to(date, 1)
			date = day_from
			
			if (okay_to_run?(CONVERSION_RATE, metrics, historical))
				query = "SELECT 
					(SELECT COUNT(*) FROM invoice
					 WHERE completed = 'y' AND
					 creationdate >= #{day_from} AND creationdate <= #{day_to})
					/
					(SELECT COUNT(*) FROM invoice
					 WHERE
					 creationdate >= #{day_from} AND creationdate <= #{day_to})
					* 100000 AS thecount"
				populate_type(CONVERSION_RATE, query, date, 0, 'na',
					:db => :shopdb)
			end

			if (okay_to_run?(GROSS_INCOME, metrics, historical))
				query = "SELECT
				CASE paymentmethod
				WHEN 'cash' THEN 0
				WHEN 'cheque' THEN 1
				WHEN 'paypal' THEN 2
				WHEN 'moneyorder' THEN 3
				WHEN 'debit' THEN 4
				WHEN 'emailmoneytransfer' THEN 5
				WHEN 'payg' THEN 6
				WHEN 'visa' THEN 7
				WHEN 'mc' THEN 8
				WHEN 'bp' THEN 9
				WHEN 'pp_mobile' THEN 10
				WHEN 'pp_land' THEN 11
				ELSE 12
				END AS payment_type,
				SUM(amountpaid) * 100 AS thecount FROM `invoice`
				WHERE completed = 'y'
				AND creationdate >= #{day_from} AND creationdate <= #{day_to}"
				populate_type(GROSS_INCOME, query, date, 0, 'na',
				 	:group_col => 'payment_type', :db => :shopdb)
			end
			
		end	
		
		def format_cell(metricid, datum)
			if (metricid == CONVERSION_RATE)
				return '%.3f%' % (datum / 1000.0)
			elsif (metricid == GROSS_INCOME)
				return '%.2f' % (datum / 100.0)
			else
				return super(metricid, datum)
			end
		end
			
	end
end
