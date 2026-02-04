# PLAN: High-Performance Watch Marketplace Architecture

## 1. Executive Summary
This project aims to transform the current PHP watch store into a scalable, containerized marketplace deployable on Kubernetes. We will transition from the current "portable" setup to a robust architecture featuring MySQL 8.0, LocalStack S3 for object storage, and a segregated role-based access control system.

## 2. Architecture Overview

### Components
1.  **Frontend/Backend (Monolith PHP)**:
    *   **Technology**: PHP 8.2 (FPM) + Nginx (sidecar or separate container).
    *   **Responsibility**: Serve HTML, handle business logic, API endpoints for AJAX.
    *   **Compliance**: PSR-12 coding standards.
2.  **Database**:
    *   **Technology**: MySQL 8.0.
    *   **Storage**: Persistent Volume Claims (PVC) in K8s.
3.  **Object Storage**:
    *   **Technology**: LocalStack (simulating AWS S3).
    *   **Usage**: Product images.
    *   **Networking**: Internal DNS `http://localstack.localstack.svc.cluster.local:4566`.

### User Roles & Workflows
1.  **Customer**:
    *   **Auth**: Login (BCrypt).
    *   **Features**:
        *   Browse Inventory (Filter by Tier: Budget/Luxury).
        *   Wishlist (AJAX add/remove).
        *   Cart (Database persisted).
        *   Checkout (Order creation).
        *   Order History.
2.  **Seller**:
    *   **Status**: Pending/Approved/Rejected (Managed by Admin).
    *   **Dashboard**: Upload products.
    *   **Image Handling**: Direct validation in PHP -> Stream to S3.
3.  **Admin**:
    *   **Dashboard**: Site analytics (Sales, User count).
    *   **Moderation**: Approve newly registered sellers.
    *   **Management**: CRUD Categories.

## 3. Database Schema Updates
We need to enhance the current schema to support the new requirements:

*   **Users**: Add `status` (pending/approved) for Sellers.
*   **Wishlist**: Ensure `user_id`, `product_id` unique constraint.
*   **Orders**: Ensure robust status tracking.

## 4. Infrastructure (Docker & K8s)

### Docker Compose (Local Dev)
*   Services: `app` (PHP+Apache/Nginx), `mysql`, `localstack`, `phpmyadmin`.
*   Networking: User-defined bridge network.

### Kubernetes (Production Simulation)
*   **Namespaces**: `watch-marketplace`.
*   **Deployments**:
    *   `web-deployment`: Scalable PHP pods (3 replicas).
    *   `mysql-deployment`: Single replica (stateful).
    *   `localstack-deployment`: S3 emulation.
*   **Services**:
    *   ClusterIP for internal comms (MySQL, S3).
    *   NodePort/LoadBalancer for Web Access.
*   **Ingress**: Nginx Controller rules for routing.

## 5. Implementation Steps

### Phase 1: Code Refactoring & Features
1.  **Refine Database**: Update `database.sql` with new Constraints and Status columns.
2.  **Enhance Auth**: Implement logical checks for Seller Approval status on login.
3.  **AJAX Wishlist**: Add endpoint `/api/wishlist` and frontend JS.
4.  **Order History**: Build `orders.php` view.
5.  **Admin**: Add "Approve Seller" UI.

### Phase 2: Dockerization
1.  Create `Dockerfile` for the PHP application (install mysqli, pdo, gd, soap, etc.).
2.  Create `docker-compose.yaml` connecting PHP, MySQL, and LocalStack.

### Phase 3: Kubernetes Manifests
1.  `k8s/01-namespace.yaml`
2.  `k8s/02-configmaps.yaml` (Env vars: DB_HOST, S3_ENDPOINT).
3.  `k8s/03-localstack.yaml` (Deployment + Service).
4.  `k8s/04-mysql.yaml` (StatefulSet + PVC + Service).
5.  `k8s/05-app.yaml` (Deployment + Service).
6.  `k8s/06-ingress.yaml`.

## 6. S3 Integration Strategy
*   **SDK**: AWS SDK for PHP.
*   **Endpoint Resolution**:
    *   *Dev (Docker)*: `http://localstack:4566`
    *   *Prod (K8s)*: `http://localstack.localstack.svc.cluster.local:4566`
*   **Bucket Initialization**: Script to create `watch-store-images` on container startup.

## 7. Next Steps
I will begin by updating the Codebase to match these new features, then proceed to generate the Infrastructure files.
