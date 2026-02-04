# KUBERNETES DEPLOYMENT GUIDE

This guide explains how to deploy your WatchStore to a local Kubernetes cluster (like Docker Desktop K8s, Minikube, or Kubeadm).

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
# 1. Create the Namespace
kubectl apply -f k8s/01-namespace.yaml

# 2. Set up Configuration (Env Variables)
kubectl apply -f k8s/02-configmaps.yaml

# 3. Start LocalStack (S3)
kubectl apply -f k8s/03-localstack.yaml

# 4. Start MySQL Database
kubectl apply -f k8s/04-mysql.yaml

# 5. Start the PHP Application (3 Replicas)
kubectl apply -f k8s/05-app.yaml
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

If using Docker Desktop, it should be available at **http://localhost**.
