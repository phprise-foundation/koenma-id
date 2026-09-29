import { Container, getContainer } from "@cloudflare/containers";

interface Env {
  KOENMA_ID_CONTAINER: DurableObjectNamespace<KoenmaIdContainer>;
  APP_SECRET: string;
  DATABASE_URL: string;
  JWT_PASSPHRASE: string;
  CORS_ALLOW_ORIGIN: string;
}

export class KoenmaIdContainer extends Container<Env> {
  defaultPort = 80;
  sleepAfter = "10m";

  envVars = {
    APP_ENV: "prod",
    APP_DEBUG: "0",
    APP_SECRET: this.env.APP_SECRET,
    DATABASE_URL: this.env.DATABASE_URL,
    JWT_PASSPHRASE: this.env.JWT_PASSPHRASE,
    CORS_ALLOW_ORIGIN: this.env.CORS_ALLOW_ORIGIN,
    JWT_SECRET_KEY: "%kernel.project_dir%/config/jwt/private.pem",
    JWT_PUBLIC_KEY: "%kernel.project_dir%/config/jwt/public.pem",
  };

  override onStart(): void {
    console.log("KoenmaID container started");
  }

  override onStop(): void {
    console.log("KoenmaID container stopped");
  }

  override onError(error: unknown): void {
    console.error("KoenmaID container error", error);
  }
}

export default {
  async fetch(request: Request, env: Env): Promise<Response> {
    const container = getContainer(env.KOENMA_ID_CONTAINER, "koenma-id");
    return container.fetch(request);
  },
};
