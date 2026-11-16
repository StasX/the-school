FROM golang:1.25-alpine AS builder
RUN apk add --no-cache git
RUN CGO_ENABLED=0 go install github.com/minio/minio@RELEASE.2025-10-15T17-29-55Z
RUN CGO_ENABLED=0 go install github.com/minio/mc@RELEASE.2025-08-13T08-35-41Z

FROM alpine:3.23 AS seeder
RUN apk add --no-cache ca-certificates curl
COPY --from=builder /go/bin/minio /usr/local/bin/minio
COPY --from=builder /go/bin/mc /usr/local/bin/mc
COPY --from=backend-builder /app/uploads/ /seeds/
COPY minio/seed.sh /seed.sh
ARG MINIO_BUCKET=school-images
RUN sh /seed.sh

FROM alpine:3.23
RUN apk add --no-cache ca-certificates curl \
    && addgroup -S -g 10001 minio && adduser -S -u 10001 -G minio minio \
    && mkdir /data && chown minio:minio /data
COPY --from=builder /go/bin/minio /usr/local/bin/minio
COPY --from=seeder --chown=10001:10001 /data/ /data/
USER minio
EXPOSE 9000 9001
HEALTHCHECK --interval=5s --timeout=5s --start-period=10s --retries=30 \
    CMD curl --fail --silent http://localhost:9000/minio/health/ready || exit 1
ENTRYPOINT ["minio"]
CMD ["server", "/data", "--console-address", ":9001"]
