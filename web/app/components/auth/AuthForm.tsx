"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Form } from "@/components/ui/form";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { cn } from "@/lib/utils";
import { Alert, AlertDescription } from "@/components/ui/alert";

interface AuthFormProps<T extends z.ZodSchema> {
  title: string;
  description?: string;
  schema: T;
  onSubmit: (data: z.infer<T>) => Promise<void>;
  submitText: string;
  children: React.ReactNode;
  footer?: React.ReactNode;
  className?: string;
}

export function AuthForm<T extends z.ZodSchema>({
  title,
  description,
  schema,
  onSubmit,
  submitText,
  children,
  footer,
  className,
}: AuthFormProps<T>) {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  const form = useForm<z.infer<T>>({
    resolver: zodResolver(schema),
    mode: "onBlur",
    defaultValues: {
      email: "",
      password: "",
      password_confirmation: "",
      first_name: "",
      last_name: "",
    } as z.infer<T>,
  });

  const handleSubmit = async (data: z.infer<T>) => {
    try {
      setIsLoading(true);
      setError(null);
      await onSubmit(data);
    } catch (error) {
      setError(error instanceof Error ? error.message : "An error occurred");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Card className={cn("w-full max-w-md mx-auto", className)}>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        {description && <CardDescription>{description}</CardDescription>}
      </CardHeader>
      <CardContent>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(handleSubmit)} className="space-y-4" role="form">
            {error && (
              <Alert variant="destructive">
                <AlertDescription>{error}</AlertDescription>
              </Alert>
            )}
            {children}
            <Button type="submit" className="w-full" disabled={isLoading}>
              {isLoading ? "Loading..." : submitText}
            </Button>
          </form>
        </Form>
      </CardContent>
      {footer && <CardFooter>{footer}</CardFooter>}
    </Card>
  );
} 