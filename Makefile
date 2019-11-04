UPLOAD_CMD := aws s3 sync --region=us-east-2 . s3://www.ilovesushidenton.com/ --delete --exclude='.git/*' --exclude='.DS_Store' --exclude='Makefile'

.PHONY: test-upload
test-upload:
	$(UPLOAD_CMD) --dryrun

.PHONY: upload
upload:
	$(UPLOAD_CMD)
