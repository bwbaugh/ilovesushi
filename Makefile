UPLOAD_CMD := aws s3 sync --region=us-east-2 . s3://www.ilovesushidenton.com/ --delete --exclude='.git/*' --exclude='.DS_Store' --exclude='Makefile'

.PHONY: test-upload
test-upload:
	$(UPLOAD_CMD) --dryrun

.PHONY: upload
upload:
	$(UPLOAD_CMD)
	$(eval $@_PRODUCTION_TAG := production-$(shell date -u '+%Y%m%dT%H%M%SZ'))
	git tag $($@_PRODUCTION_TAG)
	git push origin $($@_PRODUCTION_TAG)
