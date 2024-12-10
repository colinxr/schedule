"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Form } from "@/components/ui/form";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { cn } from "@/lib/utils";

interface AuthFormProps {
  title: string;
  description?: string;
  schema: z.ZodObject<any>;
  onSubmit: (data: any) => Promise<void>;
  submitText: string;
  children: React.ReactNode;
  footer?: React.ReactNode;
  className?: string;
}

export function AuthForm({
  title,
  description,
  schema,
  onSubmit,
  submitText,
  children,
  footer,
  className,
}: AuthFormProps) {
  const [isLoading, setIsLoading] = useState(false);
  const form = useForm({
    resolver: zodResolver(schema),
    defaultValues: {},
  });

  const handleSubmit = async (data: any) => {
    try {
      setIsLoading(true);
      await onSubmit(data);
    } catch (error) {
      console.error("Form submission error:", error);
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
          <form onSubmit={form.handleSubmit(handleSubmit)} className="space-y-4">
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