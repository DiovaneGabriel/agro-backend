<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("create or replace function public.get_products_ai_page(
    p_rows_per_page int,
    p_page int,
    p_search text,
    p_chemical_group_id chemical_groups.id%type,
    p_active_ingredient_id active_ingredients.id%type,
    p_class_id classes.id%type,
    p_action_mechanism_id action_mechanisms.id%type,
    p_action_mode_id action_modes.id%type,
    p_culture_id cultures.id%type,
    p_prague_id pragues.id%type,
    p_prague_common_name_id prague_common_names.id%type,
    p_formulation_id formulations.id%type,
    p_toxicological_class_id toxicological_classes.id%type,
    p_environmental_class_id environmental_classes.id%type,
    p_brand_id product_brands.id%type
)
returns table (
  id products.id%type,
  brand_name product_brands.name%type,
  active_ingredients text,
  total_count bigint
)
language sql
stable
as $$
with _products as (
  select
      p.id,
      pb.name as brand_name,
      string_agg(
        concat(ai.name,' (',pai.concentration,')'),
        ' + '
        order by
          coalesce(
            (replace(substring(pai.concentration from '\d+(?:[.,]\d+)?'), ',', '.'))::numeric,
            -1
          ) desc,
          ai.name
      ) as active_ingredients
  from products p
  join product_brands pb on pb.product_id = p.id
  left join product_active_ingredients pai on pai.product_id = p.id
  left join active_ingredients ai on ai.id = pai.active_ingredient_id
  group by p.id, pb.name
)
select
    p.id,
    p.brand_name,
    p.active_ingredients,
    count(*) over() as total_count
from _products p
where length(p_search) >= 3
  and (
        p.brand_name ilike concat('%', replace(p_search,' ','%'), '%')
     or p.active_ingredients ilike concat('%', replace(p_search,' ','%'), '%')
  )
  and (p_chemical_group_id is null or exists (
        select 1
        from product_active_ingredients pai
        join active_ingredients ai on ai.id = pai.active_ingredient_id
        where ai.chemical_group_id = p_chemical_group_id
          and p.id = pai.product_id
  ))
  and (p_active_ingredient_id is null or exists (
        select 1
        from product_active_ingredients pai
        where pai.active_ingredient_id = p_active_ingredient_id
          and p.id = pai.product_id
  ))
  and (p_class_id is null or exists (
        select 1
        from product_classes pc
        where pc.class_id = p_class_id
          and p.id = pc.product_id
  ))
  and (p_action_mechanism_id is null or exists (
        select 1
        from product_classes pc
        where pc.action_mechanism_id = p_action_mechanism_id
          and p.id = pc.product_id
  ))
  and (p_action_mode_id is null or exists (
        select 1
        from product_action_modes pam
        where pam.action_mode_id = p_action_mode_id
          and p.id = pam.product_id
  ))
  and (p_culture_id is null or exists (
        select 1
        from product_cultures pc
        where pc.culture_id = p_culture_id
          and p.id = pc.product_id
  ))
  and (p_prague_id is null or exists (
        select 1
        from product_pragues pp
        where pp.prague_id = p_prague_id
          and p.id = pp.product_id
  ))
  and (p_prague_common_name_id is null or exists (
        select 1
        from product_pragues pp
        join prague_common_names pcn on pcn.prague_id = pp.prague_id
        where pcn.id = p_prague_common_name_id
          and p.id = pp.product_id
  ))
  and (p_formulation_id is null or exists (
        select 1
        from products p1
        where p1.formulation_id = p_formulation_id
          and p.id = p1.id
  ))
  and (p_toxicological_class_id is null or exists (
        select 1
        from products p1
        where p1.toxicological_class_id = p_toxicological_class_id
          and p.id = p1.id
  ))
  and (p_environmental_class_id is null or exists (
        select 1
        from products p1
        where p1.environmental_class_id = p_environmental_class_id
          and p.id = p1.id
  ))
  and (p_brand_id is null or exists (
        select 1
        from product_brands pb
        where pb.id = p_brand_id
          and p.id = pb.product_id
  ))
order by p.brand_name
offset p_rows_per_page * (p_page - 1)
limit p_rows_per_page;
$$;");
    }

    public function down(): void
    {
        // Remove todas as variações (sobrecargas) da função no schema public
        DB::unprepared("do $$
declare
    r record;
begin
    for r in
        select p.oid::regprocedure as fqn
        from pg_proc p
        join pg_namespace n on n.oid = p.pronamespace
        where p.proname = 'get_products_ai_page'
          and n.nspname = 'public'
    loop
        execute format('drop function if exists %s cascade;', r.fqn);
    end loop;
end$$;");
    }
};
