# KUBERNETES DEPLOYMENT GUIDE

This guide explains how to deploy your WatchStore to a local Kubernetes cluster (like Docker Desktop K8s, Minikube, or Kubeadm).

> **Observability architecture** – the PHP pods **push** all telemetry
> (traces, logs, metrics) to a single collector pod. That collector is the only
> target Prometheus scrapes, and it forwards data on to Grafana/Loki/Tempo.
> This “alloy” design keeps the app tier simple (one outbound connection,
> no listening ports) and scales nicely.

## 1. Prerequisites
- **Docker Desktop** (with Kubernetes enabled) OR **Minikube**.
- **kubectl** command line tool.

## 2. Build the Docker Image
Before deploying, you must build your PHP application image so Kubernetes can find it.
Open your terminal in the project folder and run:

```bash
docker build -t watch-store:latest .
```

*Note: The `k8s/05-app.yaml` is set to `imagePullPolicy: Never`, which tells Kubernetes to look for the image on your local computer instead of trying to download it from the internet.*

## 3. Deploy to Kubernetes
Run these commands in order to create all the resources:

```bash
# 1. Create the Namespaces
kubectl apply -f k8s/namespace.yaml

# 2. Set up Configuration (Env Variables)
kubectl apply -f k8s/configmap.yaml

# 3. Start MySQL Database
kubectl apply -f k8s/mysql.yaml

# 5. Start the PHP Application (3 Replicas)
kubectl apply -f k8s/app.yaml

# 6. Set up Monitoring (Grafana, Loki, Tempo, Prometheus)
kubectl apply -f k8s/monitoring-stack.yaml
kubectl apply -f k8s/otel-collector.yaml
kubectl apply -f k8s/faro-collector.yaml
```

## 4. Initialization (Important!)
Since we are starting fresh in Kubernetes, the MySQL database inside the cluster is empty. You need to import your schema.

```bash
# Get the MySQL Pod Name
kubectl get pods -n watch-marketplace -l app=mysql

# (Replace 'mysql-xxxx' with the actual name from the command above)
# Copy your SQL file into the pod
kubectl cp database.sql watch-marketplace/mysql-xxxx:/tmp/database.sql

# Execute the SQL import
kubectl exec -it -n watch-marketplace mysql-xxxx -- mysql -u root -proot watch_store < /tmp/database.sql
```

## 5. View Your App
Find the Service address:
```bash
kubectl get svc -n watch-marketplace
```

If using Minikube:
```bash
minikube service php-watch -n watch-app
```

## 6. Access Monitoring (Grafana)
Grafana is exposed on NodePort 30000.
```bash
# Get the URL if using Minikube
minikube service grafana -n monitoring --url
```
Log in with:
- **URL**: http://<minikube-ip>:30000
- **User**: (Anonymous Admin access is enabled in the manifest)
